<?php

namespace App\Http\Controllers\Worker;

use App\Enums\SkillLevel;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SkillController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('skill', [
            'name' => ['required', 'string', 'max:100'],
            'level' => ['required', Rule::enum(SkillLevel::class)],
        ], [], ['name' => 'nama skill', 'level' => 'tingkat']);

        $request->user()->skills()->create($validated);

        return back()->with('status', 'Skill ditambahkan.');
    }

    public function destroy(Request $request, int $skill): RedirectResponse
    {
        $request->user()->skills()->findOrFail($skill)->delete();

        return back()->with('status', 'Skill dihapus.');
    }
}
