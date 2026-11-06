<?php

namespace Tests\Feature\Worker;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidatePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_worker_can_upload_a_photo_before_filling_the_profile(): void
    {
        $worker = User::factory()->create();

        $this->actingAs($worker)
            ->post(route('worker.photo.store'), ['photo' => UploadedFile::fake()->image('foto.jpg', 400, 400)])
            ->assertSessionHasNoErrors();

        $path = $worker->candidateProfile->photo_path;
        Storage::disk('local')->assertExists($path);
        $this->assertNotNull($worker->photoUrl());
    }

    public function test_replacing_the_photo_deletes_the_old_file(): void
    {
        $worker = User::factory()->create();
        $this->actingAs($worker)->post(route('worker.photo.store'), ['photo' => UploadedFile::fake()->image('lama.jpg', 400, 400)]);
        $oldPath = $worker->candidateProfile->photo_path;

        $this->actingAs($worker)->post(route('worker.photo.store'), ['photo' => UploadedFile::fake()->image('baru.jpg', 400, 400)]);

        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($worker->candidateProfile()->first()->photo_path);
    }

    public function test_photo_must_be_an_image_of_at_least_200_pixels(): void
    {
        $worker = User::factory()->create();

        $this->actingAs($worker)
            ->post(route('worker.photo.store'), ['photo' => UploadedFile::fake()->image('kecil.jpg', 100, 100)])
            ->assertSessionHasErrorsIn('photo', 'photo');

        $this->actingAs($worker)
            ->post(route('worker.photo.store'), ['photo' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrorsIn('photo', 'photo');

        $this->assertNull($worker->candidateProfile);
    }

    public function test_worker_can_remove_their_photo(): void
    {
        $worker = User::factory()->create();
        $this->actingAs($worker)->post(route('worker.photo.store'), ['photo' => UploadedFile::fake()->image('foto.jpg', 400, 400)]);
        $path = $worker->candidateProfile->photo_path;

        $this->actingAs($worker)->delete(route('worker.photo.destroy'));

        Storage::disk('local')->assertMissing($path);
        $this->assertNull($worker->candidateProfile()->first()->photo_path);
    }

    public function test_photo_is_visible_to_partners_only_while_the_candidate_is_active(): void
    {
        $candidate = User::factory()->memberOf(Organization::factory()->lpk()->create())->activeCandidate()->create();
        $this->actingAs($candidate)->post(route('worker.photo.store'), ['photo' => UploadedFile::fake()->image('foto.jpg', 400, 400)]);

        $this->actingAs($candidate)->get(route('candidates.photo', $candidate))->assertOk();
        $this->actingAs(User::factory()->company(Organization::factory()->connectedTo($candidate->organization)->create())->create())->get(route('candidates.photo', $candidate))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('candidates.photo', $candidate))->assertNotFound();

        $this->travel(1)->years();
        $this->travel(1)->days();

        $this->actingAs(User::factory()->lpk($candidate->organization)->create())->get(route('candidates.photo', $candidate))->assertNotFound();
    }

    public function test_photo_appears_on_the_dashboard_and_candidate_list(): void
    {
        $candidate = User::factory()->memberOf(Organization::factory()->lpk()->create())->activeCandidate()->create();
        $this->actingAs($candidate)->post(route('worker.photo.store'), ['photo' => UploadedFile::fake()->image('foto.jpg', 400, 400)]);
        $photoRoute = route('candidates.photo', $candidate);

        $this->actingAs($candidate)->get(route('dashboard'))->assertSee($photoRoute, false);
        $this->actingAs(User::factory()->lpk($candidate->organization)->create())->get(route('candidates.index'))->assertSee($photoRoute, false);
    }
}
