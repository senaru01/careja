<?php

namespace Tests\Feature\Lpk;

use App\Models\BankQuestion;
use App\Models\Organization;
use App\Models\QuestionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionBankTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_lpk_gets_the_default_categories(): void
    {
        $lpk = Organization::factory()->lpk()->create();
        $company = Organization::factory()->company()->create();

        $this->assertEqualsCanonicalizing(['Psikotes', 'Pengetahuan Umum'], $lpk->questionCategories()->pluck('name')->all());
        $this->assertSame(0, $company->questionCategories()->count());
    }

    public function test_lpk_can_manage_categories(): void
    {
        $lpk = User::factory()->lpk()->create();

        $this->actingAs($lpk)->get(route('lpk.categories.index'))->assertOk()->assertSee('Psikotes');

        $this->actingAs($lpk)->post(route('lpk.categories.store'), ['name' => 'Bahasa Jepang'])->assertSessionHasNoErrors();
        $category = $lpk->organization->questionCategories()->where('name', 'Bahasa Jepang')->firstOrFail();

        $this->actingAs($lpk)->put(route('lpk.categories.update', $category), ['name' => 'Bahasa Jepang N5'])->assertSessionHasNoErrors();
        $this->assertSame('Bahasa Jepang N5', $category->fresh()->name);

        $this->actingAs($lpk)->delete(route('lpk.categories.destroy', $category));
        $this->assertModelMissing($category);
    }

    public function test_category_names_are_unique_per_lpk(): void
    {
        $lpk = User::factory()->lpk()->create();

        $this->actingAs($lpk)->post(route('lpk.categories.store'), ['name' => 'Psikotes'])->assertSessionHasErrors('name');

        $otherLpk = User::factory()->lpk()->create();
        $this->actingAs($otherLpk)->post(route('lpk.categories.store'), ['name' => 'Bahasa Inggris'])->assertSessionHasNoErrors();
    }

    public function test_lpk_can_add_edit_and_delete_bank_questions(): void
    {
        $lpk = User::factory()->lpk()->create();
        $category = $lpk->organization->questionCategories()->firstOrFail();

        $this->actingAs($lpk)->post(route('lpk.categories.questions.store', $category), [
            'question' => '2 + 2 = ?',
            'options' => ['3', '4', '5', '', ''],
            'correct_option' => 1,
        ])->assertSessionHasNoErrors();

        $question = $category->questions()->with('options')->firstOrFail();
        $this->assertCount(3, $question->options);
        $this->assertSame('4', $question->options->firstWhere('is_correct', true)->text);

        $this->actingAs($lpk)->put(route('lpk.categories.questions.update', [$category, $question]), [
            'question' => '3 + 3 = ?',
            'options' => ['6', '7', '', '', ''],
            'correct_option' => 0,
        ])->assertRedirect(route('lpk.categories.show', $category));

        $question->refresh()->load('options');
        $this->assertSame('3 + 3 = ?', $question->question);
        $this->assertCount(2, $question->options);
        $this->assertSame('6', $question->options->firstWhere('is_correct', true)->text);

        $this->actingAs($lpk)->delete(route('lpk.categories.questions.destroy', [$category, $question]));
        $this->assertModelMissing($question);
    }

    public function test_lpk_can_search_bank_questions(): void
    {
        $lpk = User::factory()->lpk()->create();
        $category = $lpk->organization->questionCategories()->firstOrFail();
        BankQuestion::factory()->for($category, 'category')->withOptions()->create(['question' => 'Apa ibu kota Jepang?']);
        BankQuestion::factory()->for($category, 'category')->withOptions()->create(['question' => 'Berapa hasil 7 x 8?']);

        $this->actingAs($lpk)
            ->get(route('lpk.categories.show', ['category' => $category, 'search' => 'Jepang']))
            ->assertSee('Apa ibu kota Jepang?')
            ->assertDontSee('Berapa hasil 7 x 8?');
    }

    public function test_lpk_can_not_touch_another_lpks_bank(): void
    {
        $lpk = User::factory()->lpk()->create();
        $otherCategory = QuestionCategory::factory()->withQuestions(1)->create();
        $otherQuestion = $otherCategory->questions()->first();

        $this->actingAs($lpk)->get(route('lpk.categories.show', $otherCategory))->assertForbidden();
        $this->actingAs($lpk)->delete(route('lpk.categories.destroy', $otherCategory))->assertForbidden();
        $this->actingAs($lpk)->delete(route('lpk.categories.questions.destroy', [$otherCategory, $otherQuestion]))->assertForbidden();
        $this->actingAs($lpk)->post(route('lpk.categories.questions.store', $otherCategory), [
            'question' => 'Soal titipan?', 'options' => ['A', 'B'], 'correct_option' => 0,
        ])->assertForbidden();

        $this->assertModelExists($otherCategory);
        $this->assertModelExists($otherQuestion);
    }

    public function test_question_from_another_category_can_not_be_reached_through_this_category(): void
    {
        $lpk = User::factory()->lpk()->create();
        [$psikotes, $umum] = $lpk->organization->questionCategories()->get()->all();
        $question = BankQuestion::factory()->for($umum, 'category')->withOptions()->create();

        $this->actingAs($lpk)->delete(route('lpk.categories.questions.destroy', [$psikotes, $question]))->assertNotFound();
        $this->assertModelExists($question);
    }

    public function test_company_can_not_open_the_question_bank(): void
    {
        $this->actingAs(User::factory()->company()->create())->get(route('lpk.categories.index'))->assertForbidden();
    }
}
