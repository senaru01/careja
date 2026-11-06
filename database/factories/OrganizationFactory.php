<?php

namespace Database\Factories;

use App\Enums\OrganizationType;
use App\Enums\PaymentCollector;
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
            'subdomain' => fake()->unique()->lexify('org-??????'),
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

    /**
     * A company connected to the given LPKs.
     *
     * @param  Organization|iterable<Organization>  $lpks
     */
    public function connectedTo(Organization|iterable $lpks): static
    {
        $lpks = $lpks instanceof Organization ? [$lpks] : $lpks;

        return $this->company()->afterCreating(fn (Organization $company) => $company->connectedLpks()->attach(collect($lpks)->pluck('id')->all()));
    }

    /**
     * An LPK whose members pay the membership straight to its own account.
     */
    public function collectsMembershipDirectly(): static
    {
        return $this->lpk()->state(fn (array $attributes) => [
            'membership_collected_by' => PaymentCollector::Lpk,
            'bank_name' => 'BCA',
            'bank_account_number' => fake()->numerify('##########'),
            'bank_account_holder' => $attributes['name'] ?? 'LPK',
        ]);
    }
}
