<?php

namespace Database\Factories;

use App\Models\ExamToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamToken>
 */
class ExamTokenFactory extends Factory
{
    /**
     * Define the model's default state (unused token).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->activeCandidate(),
            'note' => 'Keluar dari sesi yang dimundurkan',
        ];
    }
}
