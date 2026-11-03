<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state (waiting for verification).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => config('careja.registration_fee'),
            'proof_path' => 'payments/'.fake()->uuid().'.jpg',
            'status' => PaymentStatus::Pending,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Verified,
            'verified_at' => now(),
            'expires_at' => now()->addMonths(config('careja.membership_months')),
        ]);
    }

    /**
     * A payment for another catalog item than the membership, at its listed price.
     */
    public function purpose(PaymentPurpose $purpose): static
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => $purpose,
            'amount' => $purpose->fee(),
        ]);
    }

    /**
     * A pending Midtrans QRIS payment waiting to be scanned.
     */
    public function qris(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::Midtrans,
            'proof_path' => null,
            'gateway_order_id' => 'CRJ-'.fake()->unique()->numerify('####').'-TEST',
            'qr_url' => 'https://api.sandbox.midtrans.com/v2/qris/test/qr-code',
            'payment_due_at' => now()->addMinutes(15),
        ]);
    }

    /**
     * A verified payment whose membership period has already ended.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Verified,
            'verified_at' => now()->subMonths(config('careja.membership_months'))->subDay(),
            'expires_at' => now()->subDay(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Rejected,
            'note' => 'Bukti tidak jelas',
            'verified_at' => now(),
        ]);
    }
}
