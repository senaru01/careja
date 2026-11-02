<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\CandidateDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CandidateDocument>
 */
class CandidateDocumentFactory extends Factory
{
    /**
     * Define the model's default state (a CV).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => DocumentType::Cv,
            'path' => 'documents/'.fake()->uuid().'.pdf',
            'original_name' => 'cv.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ];
    }
}
