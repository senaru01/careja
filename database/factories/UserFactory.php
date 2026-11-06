<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\CandidateDocument;
use App\Models\CandidateProfile;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Worker,
            'approved_at' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is still waiting for super admin approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_at' => null,
        ]);
    }

    /**
     * Indicate that the user is a super admin.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::SuperAdmin,
        ]);
    }

    /**
     * Indicate that the user is a staff member of an LPK.
     */
    public function lpk(?Organization $organization = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Lpk,
            'organization_id' => $organization ?? Organization::factory()->lpk(),
        ]);
    }

    /**
     * Indicate that the user is a staff member of a company.
     */
    public function company(?Organization $organization = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Company,
            'organization_id' => $organization ?? Organization::factory()->company(),
        ]);
    }

    /**
     * A job seeker who registered through the given LPK's subdomain.
     */
    public function memberOf(Organization $lpk): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Worker,
            'organization_id' => $lpk->id,
        ]);
    }

    /**
     * A job seeker with a complete profile, a CV and a verified registration payment.
     */
    public function activeCandidate(): static
    {
        return $this->afterCreating(function (User $user) {
            CandidateProfile::factory()->for($user)->create();
            CandidateDocument::factory()->for($user)->create();
            Payment::factory()->for($user)->verified()->create();
        });
    }
}
