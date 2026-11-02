<?php

namespace Database\Factories;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => OrganizationType::Company,
            'name' => fake()->company(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
        ];
    }

    public function lpk(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => OrganizationType::Lpk,
            'name' => 'LPK '.fake()->company(),
        ]);
    }

    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => OrganizationType::Company,
        ]);
    }
}
