<?php

namespace Tests\Feature;

use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_gets_its_own_dashboard(): void
    {
        $cases = [
            'Progres Rekrutmen' => User::factory()->create(),
            'Menunggu Evaluasi Anda' => User::factory()->lpk()->create(),
            'Sesi Tes Perusahaan Anda' => User::factory()->company()->create(),
            'Pembayaran Menunggu Verifikasi' => User::factory()->superAdmin()->create(),
        ];

        foreach ($cases as $heading => $user) {
            $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee($heading);
        }
    }

    public function test_new_worker_is_asked_to_complete_their_profile(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertSee('Lengkapi Profil');
    }

    public function test_expired_unsubmitted_exam_is_closed_when_the_dashboard_loads(): void
    {
        $attempt = ExamAttempt::factory()->started()->create();
        $this->travel($attempt->exam->duration_minutes + 5)->minutes();

        $this->actingAs($attempt->user)->get(route('dashboard'))->assertOk();

        $this->assertTrue($attempt->fresh()->isSubmitted());
        $this->assertSame(0, $attempt->fresh()->score);
    }
}
