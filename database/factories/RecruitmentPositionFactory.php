<?php

namespace Database\Factories;

use App\Enums\EducationLevel;
use App\Models\RecruitmentPosition;
use App\Models\RecruitmentRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecruitmentPosition>
 */
class RecruitmentPositionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recruitment_request_id' => RecruitmentRequest::factory(),
            'position' => fake()->unique()->jobTitle(),
            'quota' => 2,
            'min_education' => EducationLevel::Sma,
            'min_age' => 18,
            'max_age' => 30,
            'min_experience_years' => 0,
        ];
    }
}
