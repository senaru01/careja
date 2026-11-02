<?php

namespace Database\Factories;

use App\Models\BankQuestion;
use App\Models\BankQuestionOption;
use App\Models\QuestionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankQuestion>
 */
class BankQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_category_id' => QuestionCategory::factory(),
            'question' => fake()->unique()->sentence().'?',
        ];
    }

    public function withOptions(): static
    {
        return $this
            ->has(BankQuestionOption::factory()->state(['is_correct' => true]), 'options')
            ->has(BankQuestionOption::factory()->count(3), 'options');
    }
}
