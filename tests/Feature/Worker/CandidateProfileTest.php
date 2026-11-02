<?php

namespace Tests\Feature\Worker;

use App\Models\CandidateDocument;
use App\Models\CandidateSkill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidateProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validProfile(): array
    {
        return [
            'nik' => '3201010101010001',
            'phone' => '081234567890',
            'gender' => 'male',
            'birth_place' => 'Bandung',
            'birth_date' => '2000-01-01',
            'address' => 'Jl. Merdeka 1',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'education_level' => 'smk',
            'education_institution' => 'SMKN 1 Bandung',
            'education_major' => 'Teknik Mesin',
            'graduation_year' => 2018,
        ];
    }

    public function test_worker_can_fill_in_their_profile(): void
    {
        $worker = User::factory()->create();

        $this->actingAs($worker)
            ->put(route('worker.profile.update'), $this->validProfile())
            ->assertSessionHasNoErrors();

        $this->assertSame('3201010101010001', $worker->candidateProfile->nik);
        $this->assertTrue($worker->candidateProfile->isComplete());
    }

    public function test_profile_requires_a_sixteen_digit_nik(): void
    {
        $worker = User::factory()->create();

        $this->actingAs($worker)
            ->put(route('worker.profile.update'), ['nik' => '123'] + $this->validProfile())
            ->assertSessionHasErrors('nik');
    }

    public function test_profile_is_only_complete_with_a_cv(): void
    {
        Storage::fake('local');
        $worker = User::factory()->create();
        $this->actingAs($worker)->put(route('worker.profile.update'), $this->validProfile());

        $this->assertFalse($worker->hasCompleteCandidateProfile());

        $this->actingAs($worker)->post(route('worker.documents.store'), [
            'type' => 'cv',
            'file' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $this->assertTrue($worker->fresh()->hasCompleteCandidateProfile());
        Storage::disk('local')->assertExists($worker->documents()->first()->path);
    }

    public function test_document_upload_rejects_other_file_types(): void
    {
        Storage::fake('local');
        $worker = User::factory()->create();

        $this->actingAs($worker)->post(route('worker.documents.store'), [
            'type' => 'cv',
            'file' => UploadedFile::fake()->create('script.php', 10, 'text/x-php'),
        ])->assertSessionHasErrors('file');

        $this->assertSame(0, $worker->documents()->count());
    }

    public function test_worker_can_add_and_remove_skills(): void
    {
        $worker = User::factory()->create();

        $this->actingAs($worker)->post(route('worker.skills.store'), ['name' => 'Pengelasan', 'level' => 'advanced']);
        $skill = $worker->skills()->firstOrFail();

        $this->actingAs($worker)->delete(route('worker.skills.destroy', $skill));

        $this->assertModelMissing($skill);
    }

    public function test_worker_can_not_remove_another_workers_skill_or_document(): void
    {
        $worker = User::factory()->create();
        $skill = CandidateSkill::factory()->create();
        $document = CandidateDocument::factory()->create();

        $this->actingAs($worker)->delete(route('worker.skills.destroy', $skill))->assertNotFound();
        $this->actingAs($worker)->delete(route('worker.documents.destroy', $document))->assertForbidden();

        $this->assertModelExists($skill);
        $this->assertModelExists($document);
    }

    public function test_documents_are_visible_to_owner_and_partners_of_active_candidates_only(): void
    {
        Storage::fake('local');
        $candidate = User::factory()->activeCandidate()->create();
        $document = $candidate->documents()->first();
        Storage::disk('local')->put($document->path, 'pdf');

        $unpaidDocument = CandidateDocument::factory()->create();
        Storage::disk('local')->put($unpaidDocument->path, 'pdf');

        $lpk = User::factory()->lpk()->create();

        $this->actingAs($candidate)->get(route('documents.show', $document))->assertOk();
        $this->actingAs($lpk)->get(route('documents.show', $document))->assertOk();
        $this->actingAs($lpk)->get(route('documents.show', $unpaidDocument))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('documents.show', $document))->assertForbidden();
    }

    public function test_non_workers_can_not_open_the_worker_profile_page(): void
    {
        $this->actingAs(User::factory()->company()->create())
            ->get(route('worker.profile.edit'))
            ->assertForbidden();
    }
}
