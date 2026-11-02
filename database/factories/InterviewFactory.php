<?php

namespace Database\Factories;

use App\Enums\InterviewStatus;
use App\Models\Interview;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interview>
 */
class InterviewFactory extends Factory
{
    /**
     * Define the model's default state (scheduled).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->activeCandidate(),
            'organization_id' => Organization::factory()->company(),
            'scheduled_at' => now()->addDays(3),
            'location' => fake()->address(),
            'status' => InterviewStatus::Scheduled,
        ];
    }
}
