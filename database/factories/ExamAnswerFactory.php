<?php

namespace Database\Factories;

use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAnswer>
 */
class ExamAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_attempt_id' => ExamAttempt::factory(),
            'exam_question_id' => ExamQuestion::factory(),
            'exam_option_id' => null,
            'is_correct' => false,
        ];
    }
}
