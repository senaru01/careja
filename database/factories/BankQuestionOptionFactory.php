<?php

namespace Database\Factories;

use App\Models\BankQuestion;
use App\Models\BankQuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankQuestionOption>
 */
class BankQuestionOptionFactory extends Factory
{
    /**
     * Define the model's default state (a wrong option).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bank_question_id' => BankQuestion::factory(),
            'text' => fake()->words(3, true),
            'is_correct' => false,
        ];
    }
}
