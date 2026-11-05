<?php

namespace Database\Factories;

use App\Enums\EducationLevel;
use App\Models\Exam;
use App\Models\ExamPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamPosition>
 */
class ExamPositionFactory extends Factory
{
    /**
     * Define the model's default state: open to fresh graduates aged 18–35.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'position' => fake()->unique()->jobTitle(),
            'quota' => 2,
            'min_education' => EducationLevel::Sma,
            'min_age' => 18,
            'max_age' => 35,
            'min_experience_years' => 0,
        ];
    }

    /**
     * Open to everyone: no education, age or experience requirement.
     */
    public function unrestricted(): static
    {
        return $this->state(fn (array $attributes) => [
            'min_education' => null,
            'min_age' => null,
            'max_age' => null,
            'min_experience_years' => 0,
        ]);
    }
}
