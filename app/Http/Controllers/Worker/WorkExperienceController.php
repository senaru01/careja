<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkExperienceController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('experience', [
            'company_name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date', 'before_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'company_name' => 'nama perusahaan',
            'position' => 'jabatan',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal selesai',
            'description' => 'deskripsi pekerjaan',
        ]);

        $request->user()->workExperiences()->create($validated);

        return back()->with('status', 'Pengalaman kerja ditambahkan.');
    }

    public function destroy(Request $request, int $experience): RedirectResponse
    {
        $request->user()->workExperiences()->findOrFail($experience)->delete();

        return back()->with('status', 'Pengalaman kerja dihapus.');
    }
}
