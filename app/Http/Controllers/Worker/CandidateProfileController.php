<?php

namespace App\Http\Controllers\Worker;

use App\Enums\DocumentType;
use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Enums\SkillLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\UpdateCandidateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateProfileController extends Controller
{
    /**
     * Show the candidate profile form: personal data, education, skills,
     * work experience and documents.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load(['skills', 'workExperiences', 'documents']);

        return view('worker.profile.edit', [
            'user' => $user,
            'profile' => $user->candidateProfile()->firstOrNew(),
            'genders' => Gender::cases(),
            'educationLevels' => EducationLevel::cases(),
            'skillLevels' => SkillLevel::cases(),
            'documentTypes' => DocumentType::cases(),
            'isComplete' => $user->hasCompleteCandidateProfile(),
        ]);
    }

    public function update(UpdateCandidateProfileRequest $request): RedirectResponse
    {
        $request->user()->candidateProfile()->updateOrCreate([], $request->validated());

        return back()->with('status', 'Data diri dan pendidikan berhasil disimpan.');
    }
}
