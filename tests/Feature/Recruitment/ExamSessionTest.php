<?php

namespace Tests\Feature\Recruitment;

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamToken;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\ExamRegistrationConfirmed;
use App\Notifications\ExamRescheduled;
use App\Notifications\ExamSessionPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExamSessionTest extends TestCase
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

    private function fakeCharge(): void
    {
        Http::fake(['api.sandbox.midtrans.com/v2/charge' => fn (Request $request) => Http::response([
            'status_code' => '201',
            'transaction_id' => 'trx-'.$request['transaction_details']['order_id'],
            'order_id' => $request['transaction_details']['order_id'],
            'gross_amount' => $request['transaction_details']['gross_amount'].'.00',
            'transaction_status' => 'pending',
            'actions' => [['name' => 'generate-qr-code', 'url' => 'https://api.sandbox.midtrans.com/v2/qris/x/qr-code']],
            'expiry_time' => now('Asia/Jakarta')->addMinutes(15)->format('Y-m-d H:i:s'),
        ])]);
    }

    private function fakeSettlement(int $amount): void
    {
        Http::fake(['api.sandbox.midtrans.com/v2/*/status' => Http::response([
            'status_code' => '200', 'transaction_status' => 'settlement', 'gross_amount' => $amount.'.00', 'fraud_status' => 'accept',
        ])]);
    }

    private function settle(Payment $payment): void
    {
        $amount = $payment->amount.'.00';

        $this->postJson(route('midtrans.notification'), [
            'order_id' => $payment->gateway_order_id,
            'status_code' => '200',
            'gross_amount' => $amount,
            'transaction_status' => 'settlement',
            'signature_key' => hash('sha512', $payment->gateway_order_id.'200'.$amount.self::SERVER_KEY),
        ])->assertOk();
    }

    /**
     * @return array<string, string>
     */
    private function registration(string $payWith = 'qris'): array
    {
        return ['qualification_confirmed' => '1', 'pay_with' => $payWith];
    }

    public function test_lpk_pays_50000_and_the_session_is_published_to_active_members(): void
    {
        $lpk = User::factory()->lpk()->create();
        $exam = Exam::factory()->for($lpk->organization)->draft()->withQuestions()->create();
        $member = User::factory()->activeCandidate()->create();
        $unpaidWorker = User::factory()->create();

        $this->fakeCharge();
        $this->actingAs($lpk)->post(route('lpk.exams.publish', $exam))->assertRedirect(route('lpk.exams.show', $exam));

        $payment = $exam->payments()->firstOrFail();
        $this->assertSame(PaymentPurpose::ExamSession, $payment->purpose);
        $this->assertSame(50000, $payment->amount);
        $this->assertFalse($exam->fresh()->isPublished());

        $this->fakeSettlement(50000);
        $this->settle($payment);

        $this->assertTrue($exam->fresh()->isPublished());
        $this->assertNull($payment->fresh()->expires_at);
        Notification::assertSentTo($member, ExamSessionPublished::class);
        Notification::assertNotSentTo($unpaidWorker, ExamSessionPublished::class);
    }

    public function test_session_without_questions_can_not_be_published(): void
    {
        $lpk = User::factory()->lpk()->create();
        $exam = Exam::factory()->for($lpk->organization)->draft()->create();

        $this->actingAs($lpk)->post(route('lpk.exams.publish', $exam))->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_session_starting_within_an_hour_can_not_be_published(): void
    {
        $lpk = User::factory()->lpk()->create();
        $exam = Exam::factory()->for($lpk->organization)->draft()->withQuestions()
            ->create(['starts_at' => now()->addMinutes(30)]);

        $this->actingAs($lpk)->post(route('lpk.exams.publish', $exam))->assertForbidden();
    }

    public function test_draft_session_is_not_visible_to_workers(): void
    {
        $exam = Exam::factory()->draft()->create();

        $this->actingAs(User::factory()->activeCandidate()->create())
            ->get(route('worker.sessions.show', $exam))
            ->assertNotFound();
    }

    public function test_worker_must_confirm_the_qualifications(): void
    {
        $exam = Exam::factory()->upcoming()->withQuestions()->create();
        $worker = User::factory()->activeCandidate()->create();

        $this->actingAs($worker)
            ->post(route('worker.sessions.register', $exam), ['pay_with' => 'qris'])
            ->assertSessionHasErrors('qualification_confirmed');

        Http::assertNothingSent();
    }

    public function test_worker_pays_6500_and_becomes_ready_for_the_test(): void
    {
        $exam = Exam::factory()->upcoming()->withQuestions()->create();
        $worker = User::factory()->activeCandidate()->create();

        $this->fakeCharge();
        $this->actingAs($worker)->post(route('worker.sessions.register', $exam), $this->registration())
            ->assertRedirect(route('worker.sessions.show', $exam));

        $payment = $worker->payments()->where('purpose', PaymentPurpose::ExamRegistration)->firstOrFail();
        $this->assertSame(6500, $payment->amount);
        $this->assertSame(0, $exam->participants()->count());

        $this->fakeSettlement(6500);
        $this->settle($payment);

        $this->assertSame(1, $exam->participants()->count());
        $attempt = $exam->attempts()->firstOrFail();
        $this->assertNotNull($attempt->qualification_confirmed_at);
        $this->assertSame('Siap Tes', $attempt->statusLabel());
        $this->assertTrue($worker->hasActiveMembership(), 'A session payment must not touch the membership.');
        Notification::assertSentTo($worker, ExamRegistrationConfirmed::class);
    }

    public function test_lpk_and_company_see_how_many_participants_are_ready(): void
    {
        $exam = Exam::factory()->upcoming()->withQuestions()->create();
        ExamAttempt::factory()->for($exam)->count(3)->create();
        ExamAttempt::factory()->for($exam)->unpaid()->create();
        $lpk = User::factory()->lpk($exam->organization)->create();
        $company = User::factory()->company($exam->company)->create();

        $this->actingAs($lpk)->get(route('lpk.exams.index'))->assertOk()->assertSeeInOrder([$exam->title, '3']);
        $this->assertSame(3, $exam->participants()->count());
        $this->actingAs($company)->get(route('company.sessions.show', $exam))->assertOk()->assertSee('Peserta siap tes');
        $this->actingAs(User::factory()->company()->create())->get(route('company.sessions.show', $exam))->assertForbidden();
    }

    public function test_worker_can_not_register_twice(): void
    {
        $attempt = ExamAttempt::factory()->for(Exam::factory()->upcoming()->withQuestions())->create();

        $this->actingAs($attempt->user)
            ->post(route('worker.sessions.register', $attempt->exam), $this->registration())
            ->assertForbidden();
    }

    public function test_registration_closes_when_the_test_starts(): void
    {
        $exam = Exam::factory()->withQuestions()->create();

        $this->actingAs(User::factory()->activeCandidate()->create())
            ->post(route('worker.sessions.register', $exam), $this->registration())
            ->assertForbidden();
    }

    public function test_payment_arriving_after_the_session_ended_becomes_a_token(): void
    {
        $exam = Exam::factory()->finished()->withQuestions()->create();
        $attempt = ExamAttempt::factory()->for($exam)->unpaid()->create();
        $payment = Payment::factory()->for($attempt->user)->qris()->create([
            'purpose' => PaymentPurpose::ExamRegistration, 'exam_id' => $exam->id, 'amount' => 6500,
        ]);

        $this->fakeSettlement(6500);
        $this->settle($payment);

        $this->assertNull($attempt->fresh()->registered_at);
        $this->assertSame(1, $attempt->user->availableExamTokens());
    }

    public function test_worker_can_not_leave_a_session_that_was_not_postponed(): void
    {
        $attempt = ExamAttempt::factory()->for(Exam::factory()->upcoming()->withQuestions())->create();

        $this->actingAs($attempt->user)->post(route('worker.sessions.withdraw', $attempt->exam))->assertForbidden();

        $this->assertNull($attempt->fresh()->withdrawn_at);
        $this->assertSame(0, ExamToken::count());
    }

    public function test_lpk_can_only_postpone_the_schedule(): void
    {
        $exam = Exam::factory()->upcoming()->withQuestions()->create();
        $lpk = User::factory()->lpk($exam->organization)->create();

        $this->actingAs($lpk)->post(route('lpk.exams.reschedule', $exam), [
            'starts_at' => $exam->starts_at->copy()->subDay()->format('Y-m-d H:i'),
            'ends_at' => $exam->ends_at->format('Y-m-d H:i'),
            'reason' => 'Mencoba memajukan jadwal tes.',
        ])->assertSessionHasErrors('starts_at');

        $this->assertSame(0, $exam->reschedules()->count());
    }

    public function test_postponing_lets_participants_leave_for_a_token(): void
    {
        $exam = Exam::factory()->upcoming()->withQuestions()->create(['min_participants' => 20]);
        $lpk = User::factory()->lpk($exam->organization)->create();
        $attempt = ExamAttempt::factory()->for($exam)->create();

        $this->actingAs($lpk)->post(route('lpk.exams.reschedule', $exam), [
            'starts_at' => $exam->starts_at->copy()->addWeek()->format('Y-m-d H:i'),
            'ends_at' => $exam->ends_at->copy()->addWeek()->format('Y-m-d H:i'),
            'reason' => 'Peserta belum mencapai target minimal.',
        ])->assertSessionHasNoErrors();

        Notification::assertSentTo($attempt->user, ExamRescheduled::class);
        $this->assertSame(1, $exam->reschedules()->count());

        $this->actingAs($attempt->user)->post(route('worker.sessions.withdraw', $exam))
            ->assertRedirect(route('worker.sessions.index'));

        $this->assertTrue($attempt->fresh()->isWithdrawn());
        $this->assertSame(0, $exam->participants()->count());
        $this->assertSame(1, $attempt->user->availableExamTokens());

        // Leaving is final for that session.
        $this->actingAs($attempt->user)->post(route('worker.sessions.register', $exam), $this->registration('token'))->assertForbidden();
    }

    public function test_worker_who_registered_after_the_postponement_can_not_leave(): void
    {
        $exam = Exam::factory()->upcoming()->withQuestions()->create();
        $lpk = User::factory()->lpk($exam->organization)->create();

        $this->actingAs($lpk)->post(route('lpk.exams.reschedule', $exam), [
            'starts_at' => $exam->starts_at->copy()->addWeek()->format('Y-m-d H:i'),
            'ends_at' => $exam->ends_at->copy()->addWeek()->format('Y-m-d H:i'),
            'reason' => 'Ruang ujian sedang direnovasi.',
        ]);

        $this->travel(1)->minutes();
        $lateAttempt = ExamAttempt::factory()->for($exam)->create(['registered_at' => now()]);

        $this->actingAs($lateAttempt->user)->post(route('worker.sessions.withdraw', $exam))->assertForbidden();
    }

    public function test_token_pays_for_another_session(): void
    {
        $exam = Exam::factory()->upcoming()->withQuestions()->create();
        $worker = User::factory()->activeCandidate()->create();
        ExamToken::factory()->for($worker)->create();

        $this->actingAs($worker)->post(route('worker.sessions.register', $exam), $this->registration('token'))
            ->assertRedirect(route('worker.sessions.show', $exam));

        $this->assertSame(1, $exam->participants()->count());
        $this->assertSame(0, $worker->availableExamTokens());
        $this->assertSame(0, $worker->payments()->where('purpose', PaymentPurpose::ExamRegistration)->count());
        Http::assertNothingSent();
    }

    public function test_worker_without_tokens_can_not_pay_with_one(): void
    {
        $exam = Exam::factory()->upcoming()->withQuestions()->create();
        $worker = User::factory()->activeCandidate()->create();

        $this->actingAs($worker)->post(route('worker.sessions.register', $exam), $this->registration('token'))
            ->assertSessionHasErrors('pay_with');

        $this->assertSame(0, $exam->participants()->count());
    }

    public function test_open_sessions_are_listed_for_workers_with_their_requirements(): void
    {
        $exam = Exam::factory()->upcoming()->create(['position' => 'Juru Las SMAW', 'quota' => 7]);
        Exam::factory()->draft()->create(['position' => 'Sesi Draf Rahasia']);

        $this->actingAs(User::factory()->activeCandidate()->create())
            ->get(route('worker.sessions.index'))
            ->assertOk()
            ->assertSee('Juru Las SMAW')
            ->assertSee('Butuh 7 orang')
            ->assertDontSee('Sesi Draf Rahasia');

        $this->actingAs(User::factory()->activeCandidate()->create())
            ->get(route('worker.sessions.show', $exam))
            ->assertOk()
            ->assertSee('memenuhi kualifikasi')
            ->assertSee('6.500');
    }

    public function test_session_payment_does_not_count_as_a_pending_membership(): void
    {
        $worker = User::factory()->activeCandidate()->create();
        Payment::factory()->for($worker)->qris()->create(['purpose' => PaymentPurpose::ExamRegistration, 'amount' => 6500]);

        $this->assertFalse($worker->hasPendingPayment());
        $this->assertSame(PaymentStatus::Verified, $worker->activeMembership()->status);
    }
}
