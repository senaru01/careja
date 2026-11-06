<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamOption;
use App\Models\ExamQuestion;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    /**
     * Define the model's default state: a published session that is running now.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory()->lpk(),
            'company_id' => Organization::factory()->company(),
            'title' => 'Tes '.fake()->words(2, true),
            'position' => 'Operator Las',
            'qualifications' => 'Minimal SMK, bisa las SMAW, sehat jasmani.',
            'quota' => 2,
            'description' => fake()->sentence(),
            'duration_minutes' => 30,
            'passing_score' => 70,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'published_at' => now()->subDays(3),
            'started_at' => now()->subHour(),
        ];
    }

    /**
     * A session is always for a company connected to the LPK running it.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Exam $exam) {
            if ($exam->company_id) {
                $exam->company->connectedLpks()->syncWithoutDetaching([$exam->organization_id]);
            }
        });
    }

    /**
     * Registration closed, participants wait for the LPK to start the test.
     */
    public function waitingForStart(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subMinutes(10),
            'ends_at' => now()->addHours(8),
            'started_at' => null,
        ]);
    }

    /**
     * Not published yet (still editable), scheduled next week.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
            'started_at' => null,
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(8),
        ]);
    }

    /**
     * Published, registration still open.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHours(8),
            'started_at' => null,
        ]);
    }

    /**
     * The session is closed and the ranking is final.
     */
    public function finished(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
            'started_at' => now()->subDays(2),
        ]);
    }

    /**
     * Give the exam questions, each with one correct and two wrong options.
     */
    public function withQuestions(int $count = 3): static
    {
        return $this->afterCreating(function (Exam $exam) use ($count) {
            ExamQuestion::factory()
                ->count($count)
                ->for($exam)
                ->has(ExamOption::factory()->correct(), 'options')
                ->has(ExamOption::factory()->count(2), 'options')
                ->create();
        });
    }
}
