<?php

namespace Database\Factories;

use App\Enums\SkillLevel;
use App\Models\CandidateSkill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CandidateSkill>
 */
class CandidateSkillFactory extends Factory
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
            'name' => fake()->randomElement(['Pengelasan', 'Bubut', 'Bahasa Jepang', 'Microsoft Excel', 'Forklift']),
            'level' => fake()->randomElement(SkillLevel::cases()),
        ];
    }
}
