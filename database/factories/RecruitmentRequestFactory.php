<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\RecruitmentPosition;
use App\Models\RecruitmentRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecruitmentRequest>
 */
class RecruitmentRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Organization::factory()->company(),
            'title' => 'Rekrutmen '.fake()->monthName().' '.now()->year,
            'description' => fake()->sentence(),
            'needed_by' => now()->addMonth(),
        ];
    }

    /**
     * Give the request a position table.
     */
    public function withPositions(int $count = 2): static
    {
        return $this->has(RecruitmentPosition::factory()->count($count), 'positions');
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'closed_at' => now(),
        ]);
    }
}
