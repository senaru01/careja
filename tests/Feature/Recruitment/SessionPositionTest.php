<?php

namespace Tests\Feature\Recruitment;

use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Enums\PaymentPurpose;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamPosition;
use App\Models\ExamToken;
use App\Models\Payment;
use App\Models\User;
use App\Models\WorkExperience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SessionPositionTest extends TestCase
{
    use RefreshDatabase;

    private function worker(EducationLevel $education, int $age, int $experienceYears = 0): User
    {
        $worker = User::factory()->activeCandidate()->create();
        $worker->candidateProfile->update(['education_level' => $education, 'birth_date' => now()->subYears($age)->subMonth()]);

        if ($experienceYears > 0) {
            WorkExperience::factory()->for($worker)->create([
                'position' => 'Operator Mesin',
                'start_date' => now()->subYears($experienceYears)->subMonth(),
                'end_date' => null,
            ]);
        }

        ExamToken::factory()->for($worker)->create();

        return $worker;
    }

    /**
     * @return array<string, mixed>
     */
    private function registration(ExamPosition $position): array
    {
        return ['exam_position_id' => $position->id, 'qualification_confirmed' => '1', 'pay_with' => 'token'];
    }

    public function test_worker_registers_for_a_position_they_qualify_for(): void
    {
        $exam = Exam::factory()->upcoming()->create();
        $operator = ExamPosition::factory()->for($exam)->create(['min_education' => EducationLevel::Sma, 'min_age' => 18, 'max_age' => 30]);
        $worker = $this->worker(EducationLevel::Smk, 25);

        $this->actingAs($worker)->get(route('worker.sessions.show', $exam))->assertOk()->assertSee($operator->position);
        $this->actingAs($worker)->post(route('worker.sessions.register', $exam), $this->registration($operator))->assertSessionHasNoErrors();

        $attempt = $exam->attempts()->sole();
        $this->assertTrue($attempt->isRegistered());
        $this->assertTrue($attempt->position->is($operator));
    }

    public function test_requirements_are_checked_from_the_profile(): void
    {
        $exam = Exam::factory()->upcoming()->create();
        $qc = ExamPosition::factory()->for($exam)->create(['min_education' => EducationLevel::D3, 'min_age' => null, 'max_age' => null, 'min_experience_years' => 2]);
        $operator = ExamPosition::factory()->for($exam)->create(['min_education' => null, 'min_age' => 18, 'max_age' => 30]);

        $smaGraduate = $this->worker(EducationLevel::Sma, 25, experienceYears: 3);
        $this->actingAs($smaGraduate)->post(route('worker.sessions.register', $exam), $this->registration($qc))->assertSessionHasErrors('exam_position_id');

        $tooOld = $this->worker(EducationLevel::S1, 40, experienceYears: 1);
        $this->actingAs($tooOld)->post(route('worker.sessions.register', $exam), $this->registration($operator))->assertSessionHasErrors('exam_position_id');
        $this->actingAs($tooOld)->post(route('worker.sessions.register', $exam), $this->registration($qc))->assertSessionHasErrors('exam_position_id');

        $experienced = $this->worker(EducationLevel::D3, 28, experienceYears: 3);
        $this->actingAs($experienced)->post(route('worker.sessions.register', $exam), $this->registration($qc))->assertSessionHasNoErrors();

        $this->assertSame(1, $exam->participants()->count());
    }

    public function test_position_can_be_for_one_gender_only(): void
    {
        $exam = Exam::factory()->upcoming()->create();
        $forWomen = ExamPosition::factory()->for($exam)->unrestricted()->create(['gender' => Gender::Female]);

        $man = $this->worker(EducationLevel::Sma, 25);
        $man->candidateProfile->update(['gender' => Gender::Male]);
        $woman = $this->worker(EducationLevel::Sma, 25);
        $woman->candidateProfile->update(['gender' => Gender::Female]);

        $this->actingAs($man)->get(route('worker.sessions.show', $exam))->assertSee('Khusus Perempuan');
        $this->actingAs($man)->post(route('worker.sessions.register', $exam), $this->registration($forWomen))->assertSessionHasErrors('exam_position_id');
        $this->actingAs($woman)->post(route('worker.sessions.register', $exam), $this->registration($forWomen))->assertSessionHasNoErrors();

        $this->assertSame([$woman->id], $exam->participants()->pluck('user_id')->all());
    }

    public function test_position_is_required_and_must_belong_to_the_session(): void
    {
        $exam = Exam::factory()->upcoming()->create();
        ExamPosition::factory()->for($exam)->unrestricted()->create();
        $foreign = ExamPosition::factory()->unrestricted()->create();
        $worker = $this->worker(EducationLevel::Sma, 25);

        $this->actingAs($worker)->post(route('worker.sessions.register', $exam), ['qualification_confirmed' => '1', 'pay_with' => 'token'])
            ->assertSessionHasErrors('exam_position_id');
        $this->actingAs($worker)->post(route('worker.sessions.register', $exam), $this->registration($foreign))
            ->assertSessionHasErrors('exam_position_id');
    }

    public function test_company_invites_per_position_within_that_positions_quota(): void
    {
        Notification::fake();

        $exam = Exam::factory()->finished()->withQuestions()->create(['quota' => 3]);
        $operator = ExamPosition::factory()->for($exam)->create(['quota' => 2]);
        $qc = ExamPosition::factory()->for($exam)->create(['quota' => 1]);
        $company = User::factory()->company($exam->company)->create();
        Payment::factory()->purpose(PaymentPurpose::InterviewAccess)->verified()->for($company)->create(['exam_id' => $exam->id]);

        $operators = collect([90, 80, 70])->map(fn (int $score) => ExamAttempt::factory()->for($exam)->passed($score)->create(['exam_position_id' => $operator->id]));
        $qcs = collect([95, 85])->map(fn (int $score) => ExamAttempt::factory()->for($exam)->passed($score)->create(['exam_position_id' => $qc->id]));

        $this->assertSame($qcs[1]->user_id, $exam->ranking($qc)->firstWhere('rank', 2)->user_id);
        $this->actingAs($company)->get(route('company.sessions.show', $exam))->assertOk()->assertSee($operator->position)->assertSee($qc->position);

        $invite = fn (array $userIds, ExamPosition $position) => $this->actingAs($company)->post(route('company.interviews.store', $exam), [
            'exam_position_id' => $position->id,
            'user_ids' => $userIds,
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i'),
            'location' => 'Kantor pusat',
        ]);

        $invite($qcs->pluck('user_id')->all(), $qc)->assertSessionHasErrors('user_ids');
        $invite([$operators[0]->user_id], $qc)->assertSessionHasErrors('user_ids');
        $invite($operators->take(2)->pluck('user_id')->all(), $operator)->assertSessionHasNoErrors();
        $invite([$qcs[0]->user_id], $qc)->assertSessionHasNoErrors();

        $this->assertSame(0, $exam->openSeats($operator));
        $this->assertSame(0, $exam->openSeats($qc));
        $interview = $exam->interviews()->where('user_id', $operators[1]->user_id)->sole();
        $this->assertTrue($interview->position->is($operator));
        $this->assertSame(2, $interview->exam_rank);
    }
}
