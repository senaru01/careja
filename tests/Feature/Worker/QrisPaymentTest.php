<?php

namespace Tests\Feature\Worker;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\CandidateDocument;
use App\Models\CandidateProfile;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class QrisPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const SERVER_KEY = 'Mid-server-test-key';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.midtrans.server_key' => self::SERVER_KEY, 'services.midtrans.is_production' => false]);
        Http::preventStrayRequests();
        Notification::fake();
    }

    private function worker(): User
    {
        $worker = User::factory()->create();
        CandidateProfile::factory()->for($worker)->create();
        CandidateDocument::factory()->for($worker)->create();

        return $worker;
    }

    private function amount(): string
    {
        return config('careja.registration_fee').'.00';
    }

    /**
     * @return array<string, mixed>
     */
    private function chargeResponse(string $orderId = 'CRJ-1-ABC'): array
    {
        return [
            'status_code' => '201',
            'transaction_id' => 'trx-123',
            'order_id' => $orderId,
            'gross_amount' => $this->amount(),
            'transaction_status' => 'pending',
            'actions' => [['name' => 'generate-qr-code', 'method' => 'GET', 'url' => 'https://api.sandbox.midtrans.com/v2/qris/trx-123/qr-code']],
            'qr_string' => '000201...',
            'expiry_time' => now('Asia/Jakarta')->addMinutes(15)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function notification(Payment $payment, string $status, ?string $amount = null): array
    {
        $statusCode = $status === 'settlement' ? '200' : '201';
        $amount ??= $this->amount();

        return [
            'order_id' => $payment->gateway_order_id,
            'status_code' => $statusCode,
            'gross_amount' => $amount,
            'transaction_status' => $status,
            'fraud_status' => 'accept',
            'signature_key' => hash('sha512', $payment->gateway_order_id.$statusCode.$amount.self::SERVER_KEY),
        ];
    }

    public function test_worker_gets_a_qris_code_from_midtrans(): void
    {
        Http::fake(['api.sandbox.midtrans.com/v2/charge' => Http::response($this->chargeResponse())]);
        $worker = $this->worker();

        $this->actingAs($worker)->post(route('worker.payment.qris.store'))->assertRedirect(route('worker.payment.create'));

        $payment = $worker->payments()->firstOrFail();
        $this->assertSame(PaymentMethod::Midtrans, $payment->method);
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame('https://api.sandbox.midtrans.com/v2/qris/trx-123/qr-code', $payment->qr_url);
        $this->assertTrue($payment->payment_due_at->between(now()->addMinutes(14), now()->addMinutes(16)), 'QRIS deadline must be ~15 minutes away (WIB converted correctly).');

        Http::assertSent(fn (Request $request) => $request['payment_type'] === 'qris'
            && $request['transaction_details']['gross_amount'] === config('careja.registration_fee')
            && $request['transaction_details']['order_id'] === $payment->gateway_order_id
            && $request->hasHeader('Authorization', 'Basic '.base64_encode(self::SERVER_KEY.':')));
    }

    public function test_failed_charge_shows_an_error_and_leaves_no_payment(): void
    {
        Http::fake(['api.sandbox.midtrans.com/v2/charge' => Http::response(['status_code' => '402', 'status_message' => 'Payment channel is not activated.'], 402)]);
        $worker = $this->worker();

        $this->actingAs($worker)->post(route('worker.payment.qris.store'))->assertSessionHasErrors('payment');

        $this->assertSame(0, $worker->payments()->count());
    }

    public function test_incomplete_profile_can_not_start_a_qris_payment(): void
    {
        $this->actingAs(User::factory()->create())->post(route('worker.payment.qris.store'))->assertForbidden();
    }

    public function test_payment_page_shows_the_pending_qr_code(): void
    {
        Http::fake(['api.sandbox.midtrans.com/v2/*/status' => Http::response(['status_code' => '201', 'transaction_status' => 'pending', 'gross_amount' => $this->amount()])]);
        $worker = $this->worker();
        $payment = Payment::factory()->for($worker)->qris()->create();

        $this->actingAs($worker)->get(route('worker.payment.create'))
            ->assertOk()
            ->assertSee($payment->qr_url, false)
            ->assertSee('Selesaikan pembayaran dalam');
    }

    public function test_valid_settlement_notification_activates_the_account_for_one_year(): void
    {
        $worker = $this->worker();
        $payment = Payment::factory()->for($worker)->qris()->create();
        Http::fake(['api.sandbox.midtrans.com/v2/*/status' => Http::response(['status_code' => '200', 'transaction_status' => 'settlement', 'gross_amount' => $this->amount(), 'fraud_status' => 'accept'])]);

        $this->postJson(route('midtrans.notification'), $this->notification($payment, 'settlement'))->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::Verified, $payment->status);
        $this->assertNull($payment->verified_by);
        $this->assertSame(now()->addYear()->toDateString(), $payment->expires_at->toDateString());
        $this->assertTrue($worker->hasActiveMembership());
        Notification::assertSentTo($worker, PaymentReviewed::class);
    }

    public function test_notification_with_a_bad_signature_is_rejected(): void
    {
        $payment = Payment::factory()->for($this->worker())->qris()->create();
        $forged = ['signature_key' => 'forged'] + $this->notification($payment, 'settlement');

        $this->postJson(route('midtrans.notification'), $forged)->assertForbidden();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_signed_notification_is_double_checked_against_midtrans(): void
    {
        $payment = Payment::factory()->for($this->worker())->qris()->create();
        // The (signed) body claims settlement, but Midtrans itself still says pending.
        Http::fake(['api.sandbox.midtrans.com/v2/*/status' => Http::response(['status_code' => '201', 'transaction_status' => 'pending', 'gross_amount' => $this->amount()])]);

        $this->postJson(route('midtrans.notification'), $this->notification($payment, 'settlement'))->assertOk();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_underpaid_settlement_does_not_activate(): void
    {
        $payment = Payment::factory()->for($this->worker())->qris()->create();
        Http::fake(['api.sandbox.midtrans.com/v2/*/status' => Http::response(['status_code' => '200', 'transaction_status' => 'settlement', 'gross_amount' => '1000.00'])]);

        $this->postJson(route('midtrans.notification'), $this->notification($payment, 'settlement', '1000.00'))->assertOk();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_repeated_notifications_activate_only_once(): void
    {
        $worker = $this->worker();
        $payment = Payment::factory()->for($worker)->qris()->create();
        Http::fake(['api.sandbox.midtrans.com/v2/*/status' => Http::response(['status_code' => '200', 'transaction_status' => 'settlement', 'gross_amount' => $this->amount()])]);

        $this->postJson(route('midtrans.notification'), $this->notification($payment, 'settlement'));
        $this->postJson(route('midtrans.notification'), $this->notification($payment, 'settlement'));

        Notification::assertSentToTimes($worker, PaymentReviewed::class, 1);
    }

    public function test_expired_qris_frees_the_worker_to_pay_again(): void
    {
        $worker = $this->worker();
        $payment = Payment::factory()->for($worker)->qris()->create();
        Http::fake(['api.sandbox.midtrans.com/v2/*/status' => Http::response(['status_code' => '407', 'transaction_status' => 'expire', 'gross_amount' => $this->amount()])]);

        $this->actingAs($worker)->getJson(route('payments.status', $payment))
            ->assertOk()
            ->assertJson(['status' => 'expired', 'active' => false]);

        $this->assertFalse($worker->hasPendingPayment());
    }

    public function test_payment_paid_after_local_expiry_is_still_honoured(): void
    {
        $worker = $this->worker();
        $payment = Payment::factory()->for($worker)->qris()->create(['status' => PaymentStatus::Expired]);
        Http::fake(['api.sandbox.midtrans.com/v2/*/status' => Http::response(['status_code' => '200', 'transaction_status' => 'settlement', 'gross_amount' => $this->amount()])]);

        $this->postJson(route('midtrans.notification'), $this->notification($payment, 'settlement'))->assertOk();

        $this->assertTrue($worker->hasActiveMembership());
    }

    public function test_status_polling_activates_without_a_webhook(): void
    {
        $worker = $this->worker();
        $payment = Payment::factory()->for($worker)->qris()->create();
        Http::fake(['api.sandbox.midtrans.com/v2/*/status' => Http::response(['status_code' => '200', 'transaction_status' => 'settlement', 'gross_amount' => $this->amount()])]);

        $this->actingAs($worker)->getJson(route('payments.status', $payment))
            ->assertJson(['status' => 'verified', 'active' => true]);
    }

    public function test_worker_can_not_poll_someone_elses_payment(): void
    {
        $payment = Payment::factory()->qris()->create();

        $this->actingAs($this->worker())->getJson(route('payments.status', $payment))->assertNotFound();
    }

    public function test_admin_can_not_manually_approve_a_qris_payment(): void
    {
        $payment = Payment::factory()->for($this->worker())->qris()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->patch(route('admin.payments.update', $payment), ['status' => 'verified'])
            ->assertSessionHasErrors('payment');

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }
}
