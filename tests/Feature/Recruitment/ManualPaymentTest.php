<?php

namespace Tests\Feature\Recruitment;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Exam;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\ExamRegistrationConfirmed;
use App\Notifications\ExamSessionPublished;
use App\Notifications\PaymentSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManualPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();
        config(['services.midtrans.server_key' => null]);

        $this->admin = User::factory()->superAdmin()->create();
    }

    private function proof(): UploadedFile
    {
        return UploadedFile::fake()->image('bukti.jpg');
    }

    private function verify(Payment $payment): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.payments.update', $payment), ['status' => 'verified'])
            ->assertSessionHasNoErrors();
    }

    public function test_lpk_publishes_a_session_by_transfer_once_the_admin_verifies(): void
    {
        $lpk = User::factory()->lpk()->create();
        $exam = Exam::factory()->for($lpk->organization)->draft()->withQuestions()->create();
        $member = User::factory()->memberOf($exam->organization)->activeCandidate()->create();

        $this->actingAs($lpk)->get(route('lpk.exams.show', $exam))->assertOk()->assertSee('Bayar manual');
        $this->actingAs($lpk)->post(route('lpk.exams.publish.manual', $exam), ['proof' => $this->proof()])->assertSessionHasNoErrors();

        $payment = Payment::where('purpose', PaymentPurpose::ExamSession)->sole();
        $this->assertSame(PaymentMethod::Manual, $payment->method);
        $this->assertSame(PaymentPurpose::ExamSession, $payment->purpose);
        $this->assertSame(config('careja.exam_session_fee'), $payment->amount);
        Storage::disk('local')->assertExists($payment->proof_path);
        Notification::assertSentTo($this->admin, PaymentSubmitted::class);
        $this->actingAs($lpk)->get(route('lpk.exams.show', $exam))->assertSee('sedang diverifikasi admin');

        // A second proof while the first is pending is refused.
        $this->actingAs($lpk)->post(route('lpk.exams.publish.manual', $exam), ['proof' => $this->proof()])->assertSessionHasErrors('payment');

        $this->verify($payment);

        $this->assertTrue($exam->fresh()->isPublished());
        Notification::assertSentTo($member, ExamSessionPublished::class);
    }

    public function test_session_that_can_not_be_published_does_not_accept_a_proof(): void
    {
        $lpk = User::factory()->lpk()->create();
        $exam = Exam::factory()->for($lpk->organization)->draft()->create();

        $this->actingAs($lpk)->post(route('lpk.exams.publish.manual', $exam), ['proof' => $this->proof()])->assertForbidden();
        $this->assertSame(0, Payment::count());
    }

    public function test_company_unlocks_interviews_by_transfer(): void
    {
        $exam = Exam::factory()->finished()->withQuestions()->create();
        $company = User::factory()->company($exam->company)->create();

        $this->actingAs($company)->post(route('company.sessions.payment.manual', $exam), ['proof' => $this->proof()])->assertSessionHasNoErrors();
        $this->assertFalse($exam->hasPaidInterviewAccess());

        $this->verify(Payment::sole());

        $this->assertTrue($exam->hasPaidInterviewAccess());
        $this->assertTrue($company->can('select', $exam));
    }

    public function test_worker_joins_a_session_by_transfer_and_can_resend_after_a_rejection(): void
    {
        $exam = Exam::factory()->upcoming()->create();
        $worker = User::factory()->memberOf($exam->organization)->activeCandidate()->create();
        $register = fn (array $data = []) => $this->actingAs($worker)->post(route('worker.sessions.register', $exam), [
            'qualification_confirmed' => '1', 'pay_with' => 'manual', ...$data,
        ]);

        $register()->assertSessionHasErrors('proof');

        $register(['proof' => $this->proof()])->assertSessionHasNoErrors();
        $payment = Payment::where('purpose', PaymentPurpose::ExamRegistration)->sole();
        $this->assertFalse($exam->attempts()->sole()->isRegistered());

        $this->actingAs($this->admin)->patch(route('admin.payments.update', $payment), ['status' => 'rejected', 'note' => 'Nominal kurang']);
        $this->assertSame(PaymentStatus::Rejected, $payment->fresh()->status);
        $this->actingAs($worker)->get(route('worker.sessions.show', $exam))->assertSee('Nominal kurang');

        $register(['proof' => $this->proof()])->assertSessionHasNoErrors();
        $this->verify(Payment::where('purpose', PaymentPurpose::ExamRegistration)->pending()->sole());

        $this->assertTrue($exam->attempts()->sole()->isRegistered());
        Notification::assertSentTo($worker, ExamRegistrationConfirmed::class);
    }
}
