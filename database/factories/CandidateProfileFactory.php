<?php

namespace Database\Factories;

use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CandidateProfile>
 */
class CandidateProfileFactory extends Factory
{
    /**
     * Define the model's default state (a complete profile).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nik' => fake()->unique()->numerify('################'),
            'phone' => fake()->numerify('08##########'),
            'gender' => fake()->randomElement(Gender::cases()),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->dateTimeBetween('-40 years', '-18 years'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'education_level' => fake()->randomElement(EducationLevel::cases()),
            'education_institution' => fake()->company(),
            'education_major' => fake()->jobTitle(),
            'graduation_year' => fake()->numberBetween(2000, 2025),
            'summary' => fake()->sentence(),
        ];
    }
}
