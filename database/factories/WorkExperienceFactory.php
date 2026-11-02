<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkExperience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkExperience>
 */
class WorkExperienceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_name' => fake()->company(),
            'position' => fake()->jobTitle(),
            'start_date' => fake()->dateTimeBetween('-6 years', '-3 years'),
            'end_date' => fake()->dateTimeBetween('-2 years', '-1 month'),
            'description' => fake()->sentence(),
        ];
    }
}
