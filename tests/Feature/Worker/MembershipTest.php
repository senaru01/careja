<?php

namespace Tests\Feature\Worker;

use App\Models\CandidateDocument;
use App\Models\CandidateProfile;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Interview;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MembershipTest extends TestCase
{
    use RefreshDatabase;

    private function workerWithCompleteProfile(): User
    {
        $worker = User::factory()->create();
        CandidateProfile::factory()->for($worker)->create();
        CandidateDocument::factory()->for($worker)->create();

        return $worker;
    }

    public function test_fees_follow_the_business_model(): void
    {
        $this->assertSame(50000, config('careja.registration_fee'));
        $this->assertSame(50000, config('careja.exam_session_fee'));
        $this->assertSame(6500, config('careja.exam_registration_fee'));
    }

    public function test_verification_activates_the_account_for_one_year(): void
    {
        Notification::fake();
        $this->freezeTime();
        $admin = User::factory()->superAdmin()->create();
        $payment = Payment::factory()->for($this->workerWithCompleteProfile())->create();

        $this->actingAs($admin)->patch(route('admin.payments.update', $payment), ['status' => 'verified']);

        $this->assertSame(now()->addYear()->toDateTimeString(), $payment->fresh()->expires_at->toDateTimeString());
        $this->assertTrue($payment->user->hasActiveMembership());
    }

    public function test_candidate_disappears_from_partners_after_one_year(): void
    {
        $candidate = User::factory()->activeCandidate()->create(['name' => 'Kandidat Setahun']);
        $company = User::factory()->company()->create();

        $this->actingAs($company)->get(route('candidates.index'))->assertSee('Kandidat Setahun');

        $this->travel(1)->years();
        $this->travel(1)->days();

        $this->actingAs($company)->get(route('candidates.index'))->assertDontSee('Kandidat Setahun');
        $this->actingAs($company)->get(route('candidates.show', $candidate))->assertNotFound();
        $this->assertSame('Masa Aktif Habis', $candidate->candidateStage());
    }

    public function test_expired_member_can_not_register_for_a_session(): void
    {
        $exam = Exam::factory()->upcoming()->withQuestions()->create();
        $candidate = $this->workerWithCompleteProfile();
        Payment::factory()->for($candidate)->expired()->create();

        $this->actingAs($candidate)
            ->post(route('worker.sessions.register', $exam), ['qualification_confirmed' => '1', 'pay_with' => 'qris'])
            ->assertForbidden();

        $this->assertSame(0, $exam->attempts()->count());
    }

    public function test_company_can_still_open_an_expired_candidate_it_interviewed(): void
    {
        $company = User::factory()->company()->create();
        $interview = Interview::factory()->for($company->organization)->create();
        $interview->user->payments()->update(['expires_at' => now()->subDay()]);

        $this->actingAs($company)->get(route('candidates.show', $interview->user))->assertOk();
        $this->actingAs(User::factory()->company()->create())->get(route('candidates.show', $interview->user))->assertNotFound();
    }

    public function test_lpk_can_still_open_an_expired_candidate_it_tested(): void
    {
        $attempt = ExamAttempt::factory()->passed()->create();
        $lpk = User::factory()->lpk($attempt->exam->organization)->create();
        $attempt->user->payments()->update(['expires_at' => now()->subDay()]);

        $this->actingAs($lpk)->get(route('candidates.show', $attempt->user))->assertOk();
    }

    public function test_active_member_can_not_pay_again(): void
    {
        Storage::fake('local');
        $worker = $this->workerWithCompleteProfile();
        Payment::factory()->for($worker)->verified()->create();

        $this->actingAs($worker)
            ->post(route('worker.payment.store'), ['proof' => UploadedFile::fake()->image('bukti.jpg')])
            ->assertForbidden();
    }

    public function test_expired_member_can_renew(): void
    {
        Storage::fake('local');
        Notification::fake();
        $worker = $this->workerWithCompleteProfile();
        Payment::factory()->for($worker)->expired()->create();

        $this->actingAs($worker)
            ->post(route('worker.payment.store'), ['proof' => UploadedFile::fake()->image('bukti.jpg')])
            ->assertRedirect(route('worker.payment.create'));

        $this->assertSame(2, $worker->payments()->count());
    }

    public function test_dashboard_shows_the_expiry_date(): void
    {
        $candidate = User::factory()->activeCandidate()->create();
        $expiresAt = $candidate->activeMembership()->expires_at;

        $this->actingAs($candidate)
            ->get(route('dashboard'))
            ->assertSee('Akun Aktif')
            ->assertSee($expiresAt->translatedFormat('d F Y'));
    }
}
