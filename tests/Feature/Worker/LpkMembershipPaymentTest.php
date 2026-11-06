<?php

namespace Tests\Feature\Worker;

use App\Enums\PaymentCollector;
use App\Enums\PaymentStatus;
use App\Models\CandidateDocument;
use App\Models\CandidateProfile;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Each LPK has its own membership price and decides where members pay:
 * through Careja (QRIS / Careja admin) or straight to the LPK's account,
 * verified by the LPK itself.
 */
class LpkMembershipPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.midtrans.server_key' => 'Mid-server-test-key', 'services.midtrans.is_production' => false]);
        Http::preventStrayRequests();
        Notification::fake();
        Storage::fake('local');
    }

    private function member(Organization $lpk): User
    {
        $worker = User::factory()->memberOf($lpk)->create();
        CandidateProfile::factory()->for($worker)->create();
        CandidateDocument::factory()->for($worker)->create();

        return $worker;
    }

    public function test_membership_through_careja_uses_the_lpk_price_for_qris(): void
    {
        $lpk = Organization::factory()->lpk()->create(['membership_fee' => 75000]);
        $worker = $this->member($lpk);
        Http::fake(['api.sandbox.midtrans.com/v2/charge' => fn (Request $request) => Http::response([
            'status_code' => '201',
            'order_id' => $request['transaction_details']['order_id'],
            'gross_amount' => '75000.00',
            'transaction_status' => 'pending',
            'actions' => [['name' => 'generate-qr-code', 'url' => 'https://api.sandbox.midtrans.com/v2/qris/x/qr-code']],
            'expiry_time' => now('Asia/Jakarta')->addMinutes(15)->format('Y-m-d H:i:s'),
        ])]);

        $this->actingAs($worker)->get(route('worker.payment.create'))->assertSee('75.000')->assertSee('Bayar dengan QRIS');
        $this->actingAs($worker)->post(route('worker.payment.qris.store'))->assertRedirect(route('worker.payment.create'));

        $payment = $worker->payments()->sole();
        $this->assertSame(75000, $payment->amount);
        $this->assertSame($lpk->id, $payment->lpk_id);
        $this->assertSame(PaymentCollector::Careja, $payment->collected_by);
        Http::assertSent(fn (Request $request) => $request['transaction_details']['gross_amount'] === 75000);
    }

    public function test_lpk_collecting_directly_shows_its_bank_account_and_takes_no_qris(): void
    {
        $lpk = Organization::factory()->collectsMembershipDirectly()->create(['bank_account_number' => '9988776655']);
        $worker = $this->member($lpk);

        $this->actingAs($worker)->get(route('worker.payment.create'))
            ->assertOk()
            ->assertSee('9988776655')
            ->assertDontSee('Bayar dengan QRIS');

        $this->actingAs($worker)->post(route('worker.payment.qris.store'))->assertStatus(503);
        $this->assertSame(0, Payment::count());
    }

    public function test_lpk_verifies_transfers_to_its_own_account_instead_of_the_admin(): void
    {
        $lpk = Organization::factory()->collectsMembershipDirectly()->create(['membership_fee' => 60000]);
        $staff = User::factory()->lpk($lpk)->create();
        $admin = User::factory()->superAdmin()->create();
        $worker = $this->member($lpk);

        $this->actingAs($worker)->post(route('worker.payment.store'), ['proof' => UploadedFile::fake()->image('bukti.jpg')])
            ->assertSessionHas('status', 'Bukti pembayaran terkirim. LPK Anda akan memverifikasinya secepatnya.');

        $payment = $worker->payments()->sole();
        $this->assertSame(60000, $payment->amount);
        $this->assertTrue($payment->isCollectedByLpk());
        Notification::assertSentTo($staff, PaymentSubmitted::class);
        Notification::assertNotSentTo($admin, PaymentSubmitted::class);

        $this->actingAs($staff)->get(route('lpk.payments.index'))->assertOk()->assertSee($worker->name);
        $this->actingAs($staff)->get(route('lpk.payments.proof', $payment))->assertOk();
        $this->actingAs($staff)->patch(route('lpk.payments.update', $payment), ['status' => 'verified'])->assertSessionHas('status');

        $this->assertSame(PaymentStatus::Verified, $payment->fresh()->status);
        $this->assertTrue($worker->hasActiveMembership());
        $this->assertSame($staff->id, $payment->fresh()->verified_by);
    }

    public function test_lpk_can_not_see_or_verify_payments_it_does_not_collect(): void
    {
        $lpk = Organization::factory()->collectsMembershipDirectly()->create();
        $staff = User::factory()->lpk($lpk)->create();

        $viaCareja = Payment::factory()->for($this->member($lpk))->create(['lpk_id' => $lpk->id]);
        $otherLpk = Organization::factory()->collectsMembershipDirectly()->create();
        $otherLpkPayment = Payment::factory()->for($this->member($otherLpk))->create(['lpk_id' => $otherLpk->id]);
        $otherLpkPayment->forceFill(['collected_by' => PaymentCollector::Lpk])->save();

        foreach ([$viaCareja, $otherLpkPayment] as $payment) {
            $this->actingAs($staff)->get(route('lpk.payments.proof', $payment))->assertNotFound();
            $this->actingAs($staff)->patch(route('lpk.payments.update', $payment), ['status' => 'verified'])->assertNotFound();
            $this->assertTrue($payment->fresh()->isPending());
        }

        $this->actingAs($staff)->get(route('lpk.payments.index'))->assertDontSee($otherLpkPayment->user->name);
    }

    public function test_membership_through_careja_is_still_verified_by_the_admin(): void
    {
        $lpk = Organization::factory()->lpk()->create();
        $staff = User::factory()->lpk($lpk)->create();
        $admin = User::factory()->superAdmin()->create();
        $worker = $this->member($lpk);

        $this->actingAs($worker)->post(route('worker.payment.store'), ['proof' => UploadedFile::fake()->image('bukti.jpg')]);

        $this->assertSame(PaymentCollector::Careja, $worker->payments()->sole()->collected_by);
        Notification::assertSentTo($admin, PaymentSubmitted::class);
        Notification::assertNotSentTo($staff, PaymentSubmitted::class);
    }
}
