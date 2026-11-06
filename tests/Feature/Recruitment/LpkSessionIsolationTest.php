<?php

namespace Tests\Feature\Recruitment;

use App\Enums\PaymentPurpose;
use App\Models\Exam;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\ExamSessionPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Members only see and join the test sessions of their own LPK, at that LPK's prices.
 */
class LpkSessionIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_only_see_the_sessions_of_their_own_lpk(): void
    {
        $ownSession = Exam::factory()->upcoming()->withQuestions()->create(['position' => 'Operator LPK Sendiri']);
        $otherSession = Exam::factory()->upcoming()->withQuestions()->create(['position' => 'Operator LPK Lain']);
        $member = User::factory()->memberOf($ownSession->organization)->activeCandidate()->create();

        $this->actingAs($member)->get(route('worker.sessions.index'))
            ->assertSee('Operator LPK Sendiri')
            ->assertDontSee('Operator LPK Lain');
        $this->actingAs($member)->get(route('dashboard'))->assertDontSee('Operator LPK Lain');

        $this->actingAs($member)->get(route('worker.sessions.show', $otherSession))->assertNotFound();
        $this->actingAs($member)->post(route('worker.sessions.register', $otherSession), ['qualification_confirmed' => '1', 'pay_with' => 'qris'])
            ->assertNotFound();
        $this->assertSame(0, $otherSession->attempts()->count());
    }

    public function test_published_session_is_only_announced_to_members_of_that_lpk(): void
    {
        Notification::fake();
        $exam = Exam::factory()->draft()->withQuestions()->create();
        $member = User::factory()->memberOf($exam->organization)->activeCandidate()->create();
        $outsider = User::factory()->memberOf(Organization::factory()->lpk()->create())->activeCandidate()->create();
        $payment = Payment::factory()->for(User::factory()->lpk($exam->organization)->create())->purpose(PaymentPurpose::ExamSession)->create(['exam_id' => $exam->id]);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->patch(route('admin.payments.update', $payment), ['status' => 'verified'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($exam->fresh()->isPublished());
        Notification::assertSentTo($member, ExamSessionPublished::class);
        Notification::assertNotSentTo($outsider, ExamSessionPublished::class);
    }

    public function test_session_fees_follow_the_lpk_running_the_session(): void
    {
        Storage::fake('local');
        Notification::fake();
        $lpk = Organization::factory()->lpk()->create(['exam_registration_fee' => 12000, 'exam_session_fee' => 80000]);
        $exam = Exam::factory()->for($lpk)->upcoming()->withQuestions()->create();
        $member = User::factory()->memberOf($lpk)->activeCandidate()->create();

        $this->actingAs($member)->get(route('worker.sessions.show', $exam))->assertSee('12.000');
        $this->actingAs($member)->post(route('worker.sessions.register', $exam), [
            'qualification_confirmed' => '1', 'pay_with' => 'manual', 'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertSessionHasNoErrors();

        $payment = Payment::where('purpose', PaymentPurpose::ExamRegistration)->sole();
        $this->assertSame(12000, $payment->amount);
        $this->assertSame($lpk->id, $payment->lpk_id);

        $draft = Exam::factory()->for($lpk)->draft()->withQuestions()->create();
        $this->actingAs(User::factory()->lpk($lpk)->create())->get(route('lpk.exams.show', $draft))->assertSee('80.000');
    }
}
