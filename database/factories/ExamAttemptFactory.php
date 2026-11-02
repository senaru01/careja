<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttempt>
 */
class ExamAttemptFactory extends Factory
{
    /**
     * Define the model's default state (paid seat, not started).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory()->withQuestions(),
            'user_id' => User::factory()->activeCandidate(),
            'qualification_confirmed_at' => now()->subDay(),
            'registered_at' => now()->subDay(),
        ];
    }

    /**
     * Seat held while the QRIS is not paid yet.
     */
    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'registered_at' => null,
        ]);
    }

    public function started(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => now(),
        ]);
    }

    public function passed(int $score = 90): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => now()->subMinutes(20),
            'submitted_at' => now()->subMinutes(5),
            'total_questions' => 10,
            'correct_count' => intdiv($score, 10),
            'score' => $score,
            'passed' => true,
        ]);
    }

    public function failed(int $score = 40): static
    {
        return $this->passed($score)->state(fn (array $attributes) => [
            'passed' => false,
        ]);
    }
}
