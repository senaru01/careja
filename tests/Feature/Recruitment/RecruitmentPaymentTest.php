<?php

namespace Tests\Feature\Recruitment;

use App\Enums\InterviewStatus;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Interview;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RecruitmentPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const SERVER_KEY = 'Mid-server-test-key';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.midtrans.server_key' => self::SERVER_KEY,
            'services.midtrans.is_production' => false,
            'careja.interview_fee' => 500000,
            'careja.medical_checkup_fee' => 600000,
        ]);
        Http::preventStrayRequests();
        Notification::fake();
    }

    private function fakeCharge(string $amount): void
    {
        Http::fake(['api.sandbox.midtrans.com/v2/charge' => Http::response([
            'status_code' => '201',
            'transaction_id' => 'trx-123',
            'gross_amount' => $amount,
            'transaction_status' => 'pending',
            'actions' => [['name' => 'generate-qr-code', 'method' => 'GET', 'url' => 'https://api.sandbox.midtrans.com/v2/qris/trx-123/qr-code']],
            'expiry_time' => now('Asia/Jakarta')->addMinutes(15)->format('Y-m-d H:i:s'),
        ])]);
    }

    private function settle(Payment $payment, string $amount): void
    {
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
    }

    /**
     * @return array{0: Exam, 1: User, 2: ExamAttempt}
     */
    private function finishedSession(): array
    {
        $exam = Exam::factory()->finished()->withQuestions()->create(['quota' => 2]);
        $company = User::factory()->company($exam->company)->create();
        $attempt = ExamAttempt::factory()->for($exam)->passed(90)->create();

        return [$exam, $company, $attempt];
    }

    private function passedInterview(): Interview
    {
        return Interview::factory()->create(['status' => InterviewStatus::Passed]);
    }

    public function test_company_can_not_invite_before_paying_the_interview_fee(): void
    {
        [$exam, $company, $attempt] = $this->finishedSession();

        $this->actingAs($company)->get(route('company.sessions.show', $exam))
            ->assertOk()
            ->assertSee('Rp 500.000')
            ->assertSee(route('company.sessions.payment.store', $exam));

        $this->actingAs($company)->post(route('company.interviews.store', $exam), [
            'user_ids' => [$attempt->user_id],
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i'),
            'location' => 'Kantor pusat',
        ])->assertForbidden();

        $this->assertDatabaseCount('interviews', 0);
    }

    public function test_paid_interview_fee_unlocks_inviting_from_the_session(): void
    {
        [$exam, $company, $attempt] = $this->finishedSession();
        $this->fakeCharge('500000.00');

        $this->actingAs($company)->post(route('company.sessions.payment.store', $exam))
            ->assertRedirect(route('company.sessions.show', $exam));

        $payment = Payment::where('purpose', PaymentPurpose::InterviewAccess)->sole();
        $this->assertSame(500000, $payment->amount);
        $this->assertSame($exam->id, $payment->exam_id);
        Http::assertSent(fn (Request $request) => $request['item_details'][0]['id'] === 'interview_access');

        $this->settle($payment, '500000.00');

        $this->actingAs($company)->post(route('company.interviews.store', $exam), [
            'user_ids' => [$attempt->user_id],
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i'),
            'location' => 'Kantor pusat',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('interviews', ['exam_id' => $exam->id, 'user_id' => $attempt->user_id]);
    }

    public function test_interview_fee_is_paid_only_once_per_session(): void
    {
        [$exam, $company] = $this->finishedSession();
        Payment::factory()->purpose(PaymentPurpose::InterviewAccess)->verified()->for($company)->create(['exam_id' => $exam->id]);

        $this->actingAs($company)->post(route('company.sessions.payment.store', $exam))->assertForbidden();

        $this->assertSame(1, Payment::where('purpose', PaymentPurpose::InterviewAccess)->count());
    }

    public function test_interview_fee_can_not_be_paid_while_the_session_is_open(): void
    {
        $exam = Exam::factory()->upcoming()->create();
        $company = User::factory()->company($exam->company)->create();

        $this->actingAs($company)->post(route('company.sessions.payment.store', $exam))->assertForbidden();
    }

    public function test_company_can_not_record_medical_before_the_candidate_paid(): void
    {
        $interview = $this->passedInterview();
        $company = User::factory()->company($interview->organization)->create();

        $this->actingAs($company)->put(route('company.interviews.medical', $interview), [
            'medical_status' => 'fit',
            'medical_notes' => 'Klinik Sehat, semua normal.',
        ])->assertForbidden();

        $this->assertNull($interview->fresh()->medical_status);
    }

    public function test_candidate_pays_the_medical_checkup_after_passing_the_interview(): void
    {
        $interview = $this->passedInterview();
        $this->fakeCharge('600000.00');

        $this->actingAs($interview->user)->post(route('worker.medical-payment.store', $interview))
            ->assertRedirect(route('dashboard'));

        $payment = Payment::where('purpose', PaymentPurpose::MedicalCheckup)->sole();
        $this->assertSame(600000, $payment->amount);
        $this->assertSame($interview->id, $payment->interview_id);

        $this->settle($payment, '600000.00');

        $this->assertSame(PaymentStatus::Verified, $payment->fresh()->status);
        $this->assertTrue($interview->hasPaidMedicalCheckup());
    }

    public function test_worker_can_not_pay_the_medical_checkup_of_another_candidate(): void
    {
        $interview = $this->passedInterview();

        $this->actingAs(User::factory()->create())->post(route('worker.medical-payment.store', $interview))->assertForbidden();

        $this->assertSame(0, Payment::where('purpose', PaymentPurpose::MedicalCheckup)->count());
    }

    public function test_medical_checkup_can_not_be_paid_before_the_interview_is_passed(): void
    {
        $interview = Interview::factory()->create();

        $this->actingAs($interview->user)->post(route('worker.medical-payment.store', $interview))->assertForbidden();
    }
}
