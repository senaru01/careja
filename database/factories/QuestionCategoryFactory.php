<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\QuestionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionCategory>
 */
class QuestionCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory()->lpk(),
            'name' => 'Kategori '.fake()->unique()->word(),
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Fill the category with questions, each with one correct and three wrong options.
     */
    public function withQuestions(int $count = 10): static
    {
        return $this->has(BankQuestionFactory::new()->count($count)->withOptions(), 'questions');
    }
}
