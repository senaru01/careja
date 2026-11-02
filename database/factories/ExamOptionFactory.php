<?php

namespace Database\Factories;

use App\Models\ExamOption;
use App\Models\ExamQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamOption>
 */
class ExamOptionFactory extends Factory
{
    /**
     * Define the model's default state (a wrong option).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_question_id' => ExamQuestion::factory(),
            'text' => fake()->words(3, true),
            'is_correct' => false,
        ];
    }

    public function correct(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_correct' => true,
        ]);
    }
}
