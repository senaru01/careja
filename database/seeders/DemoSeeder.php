<?php

namespace Database\Seeders;

use App\Models\BankQuestion;
use App\Models\CandidateSkill;
use App\Models\Exam;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkExperience;
use Illuminate\Database\Seeder;

/**
 * Sample LPK, company, exam and candidates for trying the full flow locally.
 * Run with: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $lpk = Organization::factory()->lpk()->create(['name' => 'LPK Demo Sejahtera']);
        $company = Organization::factory()->company()->create(['name' => 'PT Demo Industri']);

        User::factory()->lpk($lpk)->create(['name' => 'Admin LPK Demo', 'email' => 'lpk@demo.test']);
        User::factory()->company($company)->create(['name' => 'HRD PT Demo', 'email' => 'hrd@demo.test']);

        // Sample question bank: 30 questions in each default category.
        foreach ($lpk->questionCategories as $category) {
            BankQuestion::factory()->count(30)->for($category, 'category')->withOptions()->create();
        }

        // One published session open for registration and one draft waiting for the LPK's payment.
        Exam::factory()->for($lpk)->for($company, 'company')->upcoming()->withQuestions(5)->create([
            'title' => 'Tes Teori Dasar K3 & Teknik',
            'position' => 'Operator Produksi',
            'qualifications' => 'Pria/Wanita 18-30 tahun, minimal SMA/SMK, sehat jasmani dan rohani.',
            'quota' => 5,
            'min_participants' => 10,
            'duration_minutes' => 15,
            'passing_score' => 60,
        ]);

        Exam::factory()->for($lpk)->for($company, 'company')->draft()->withQuestions(5)->create([
            'title' => 'Tes Juru Las SMAW',
            'position' => 'Juru Las (Welder)',
            'qualifications' => 'SMK Teknik Las/Mesin, bisa las SMAW posisi 1G-3G, sertifikat BNSP diutamakan.',
            'quota' => 3,
        ]);

        foreach (range(1, 3) as $number) {
            $candidate = User::factory()->activeCandidate()->create([
                'name' => "Kandidat Demo {$number}",
                'email' => "kandidat{$number}@demo.test",
            ]);

            CandidateSkill::factory()->count(2)->for($candidate)->create();
            WorkExperience::factory()->for($candidate)->create();
        }
    }
}
