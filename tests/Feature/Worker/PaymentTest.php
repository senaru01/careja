<?php

namespace Tests\Feature\Worker;

use App\Enums\PaymentStatus;
use App\Models\CandidateDocument;
use App\Models\CandidateProfile;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentReviewed;
use App\Notifications\PaymentSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function workerWithCompleteProfile(): User
    {
        $worker = User::factory()->create();
        CandidateProfile::factory()->for($worker)->create();
        CandidateDocument::factory()->for($worker)->create();

        return $worker;
    }

    public function test_payment_page_can_be_rendered(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('worker.payment.create'))
            ->assertOk()
            ->assertSee(number_format(config('careja.registration_fee'), 0, ',', '.'));
    }

    public function test_worker_with_complete_profile_can_upload_payment_proof(): void
    {
        Storage::fake('local');
        Notification::fake();
        $admin = User::factory()->superAdmin()->create();
        $worker = $this->workerWithCompleteProfile();

        $this->actingAs($worker)->post(route('worker.payment.store'), [
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect(route('worker.payment.create'));

        $payment = $worker->payments()->firstOrFail();
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame(config('careja.registration_fee'), $payment->amount);
        Storage::disk('local')->assertExists($payment->proof_path);
        Notification::assertSentTo($admin, PaymentSubmitted::class);
    }

    public function test_worker_with_incomplete_profile_can_not_pay(): void
    {
        Storage::fake('local');

        $this->actingAs(User::factory()->create())
            ->post(route('worker.payment.store'), ['proof' => UploadedFile::fake()->image('bukti.jpg')])
            ->assertForbidden();
    }

    public function test_worker_can_not_submit_a_second_proof_while_one_is_pending(): void
    {
        Storage::fake('local');
        $worker = $this->workerWithCompleteProfile();
        Payment::factory()->for($worker)->create();

        $this->actingAs($worker)
            ->post(route('worker.payment.store'), ['proof' => UploadedFile::fake()->image('bukti.jpg')])
            ->assertForbidden();
    }

    public function test_worker_can_upload_again_after_rejection(): void
    {
        Storage::fake('local');
        Notification::fake();
        $worker = $this->workerWithCompleteProfile();
        Payment::factory()->for($worker)->rejected()->create();

        $this->actingAs($worker)
            ->post(route('worker.payment.store'), ['proof' => UploadedFile::fake()->image('bukti.jpg')])
            ->assertRedirect();

        $this->assertSame(2, $worker->payments()->count());
    }

    public function test_super_admin_verification_makes_the_worker_an_active_candidate(): void
    {
        Notification::fake();
        $admin = User::factory()->superAdmin()->create();
        $worker = $this->workerWithCompleteProfile();
        $payment = Payment::factory()->for($worker)->create();

        $this->assertFalse(User::activeCandidates()->whereKey($worker->id)->exists());

        $this->actingAs($admin)
            ->patch(route('admin.payments.update', $payment), ['status' => 'verified'])
            ->assertSessionHasNoErrors();

        $this->assertSame(PaymentStatus::Verified, $payment->fresh()->status);
        $this->assertTrue($payment->fresh()->verifier->is($admin));
        $this->assertTrue(User::activeCandidates()->whereKey($worker->id)->exists());
        Notification::assertSentTo($worker, PaymentReviewed::class);
    }

    public function test_rejecting_a_payment_requires_a_reason(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $payment = Payment::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.payments.update', $payment), ['status' => 'rejected'])
            ->assertSessionHasErrors('note');

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_processed_payments_can_not_be_changed(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $payment = Payment::factory()->verified()->create();

        $this->actingAs($admin)
            ->patch(route('admin.payments.update', $payment), ['status' => 'rejected', 'note' => 'x'])
            ->assertSessionHasErrors('payment');

        $this->assertSame(PaymentStatus::Verified, $payment->fresh()->status);
    }

    public function test_only_super_admin_can_verify_payments(): void
    {
        $payment = Payment::factory()->create();

        foreach ([User::factory()->create(), User::factory()->lpk()->create(), User::factory()->company()->create()] as $user) {
            $this->actingAs($user)
                ->patch(route('admin.payments.update', $payment), ['status' => 'verified'])
                ->assertForbidden();
        }

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }
}
