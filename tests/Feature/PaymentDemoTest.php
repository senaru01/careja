<?php

namespace Tests\Feature;

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentDemoTest extends TestCase
{
    use RefreshDatabase;

    private const SERVER_KEY = 'Mid-server-test-key';

    private const DEMO_KEY = 'review-key-123';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.midtrans.server_key' => self::SERVER_KEY,
            'services.midtrans.is_production' => false,
            'careja.payment_demo_key' => self::DEMO_KEY,
        ]);
        Http::preventStrayRequests();
        Notification::fake();
    }

    private function fakeCharge(): void
    {
        Http::fake(['api.sandbox.midtrans.com/v2/charge' => Http::response([
            'status_code' => '201',
            'transaction_id' => 'trx-123',
            'transaction_status' => 'pending',
            'actions' => [['name' => 'generate-qr-code', 'method' => 'GET', 'url' => 'https://api.sandbox.midtrans.com/v2/qris/trx-123/qr-code']],
            'expiry_time' => now('Asia/Jakarta')->addMinutes(15)->format('Y-m-d H:i:s'),
        ])]);
    }

    public function test_demo_page_is_hidden_with_a_wrong_key(): void
    {
        $this->get(route('payment-demo.index', 'wrong-key'))->assertNotFound();
    }

    public function test_demo_page_is_off_when_no_key_is_configured(): void
    {
        config(['careja.payment_demo_key' => null]);

        $this->get('/demo-pembayaran/anything')->assertNotFound();
    }

    public function test_reviewer_sees_every_catalog_item_without_logging_in(): void
    {
        $this->get(route('payment-demo.index', self::DEMO_KEY))
            ->assertOk()
            ->assertSeeInOrder(['Keanggotaan', 'Pendaftaran Tes', 'Penerbitan Sesi Tes', 'Pengambilan Kandidat Wawancara', 'Medical Checkup']);
    }

    /**
     * @return array<string, array{0: PaymentPurpose}>
     */
    public static function purposes(): array
    {
        return collect(PaymentPurpose::cases())->mapWithKeys(fn (PaymentPurpose $purpose) => [$purpose->value => [$purpose]])->all();
    }

    #[DataProvider('purposes')]
    public function test_reviewer_gets_a_real_qris_at_the_catalog_price(PaymentPurpose $purpose): void
    {
        $this->fakeCharge();

        $response = $this->post(route('payment-demo.store', self::DEMO_KEY), ['purpose' => $purpose->value]);

        $payment = Payment::sole();
        $response->assertRedirect(route('payment-demo.show', ['demoKey' => self::DEMO_KEY, 'payment' => $payment]));
        $this->assertTrue($payment->is_demo);
        $this->assertSame($purpose, $payment->purpose);
        $this->assertSame($purpose->fee(), $payment->amount);

        $this->get(route('payment-demo.show', ['demoKey' => self::DEMO_KEY, 'payment' => $payment]))
            ->assertOk()
            ->assertSee('https://api.sandbox.midtrans.com/v2/qris/trx-123/qr-code');
    }

    public function test_unknown_item_is_rejected(): void
    {
        $this->post(route('payment-demo.store', self::DEMO_KEY), ['purpose' => 'free_lunch'])
            ->assertSessionHasErrors('purpose');

        $this->assertSame(0, Payment::count());
    }

    public function test_settled_demo_membership_activates_nothing(): void
    {
        $this->fakeCharge();
        $this->post(route('payment-demo.store', self::DEMO_KEY), ['purpose' => PaymentPurpose::Membership->value]);
        $payment = Payment::sole();
        $amount = $payment->amount.'.00';

        Http::fake(['api.sandbox.midtrans.com/v2/*/status' => Http::response([
            'status_code' => '200', 'transaction_status' => 'settlement', 'gross_amount' => $amount, 'fraud_status' => 'accept',
        ])]);
        $this->postJson(route('midtrans.notification'), [
            'order_id' => $payment->gateway_order_id,
            'status_code' => '200',
            'gross_amount' => $amount,
            'transaction_status' => 'settlement',
            'signature_key' => hash('sha512', $payment->gateway_order_id.'200'.$amount.self::SERVER_KEY),
        ])->assertOk();

        $this->assertSame(PaymentStatus::Verified, $payment->fresh()->status);
        $this->assertSame(0, User::activeCandidates()->count());
        $this->assertFalse($payment->user->hasActiveMembership());
        Notification::assertNothingSent();
    }

    public function test_status_of_a_real_payment_is_not_exposed(): void
    {
        $payment = Payment::factory()->qris()->create();

        $this->getJson(route('payment-demo.status', ['demoKey' => self::DEMO_KEY, 'payment' => $payment]))->assertNotFound();
    }
}
