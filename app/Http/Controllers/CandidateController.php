<?php

namespace App\Http\Controllers;

use App\Enums\EducationLevel;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateController extends Controller
{
    /**
     * List candidates whose membership is paid, with filters. An LPK sees its
     * own members, a company the members of its connected LPKs, the super
     * admin everyone.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'education_level' => ['nullable', 'string'],
            'skill' => ['nullable', 'string', 'max:100'],
            'theory' => ['nullable', 'in:passed,failed,none'],
            'min_age' => ['nullable', 'integer', 'min:15', 'max:70'],
            'max_age' => ['nullable', 'integer', 'min:15', 'max:70'],
            'experience' => ['nullable', 'in:fresh,experienced'],
            'lpk' => ['nullable', 'integer'],
        ]);

        $viewer = $request->user();
        $lpkIds = $viewer->visibleLpkIds();
        $lpks = Organization::lpks()->when($lpkIds !== null, fn (Builder $query) => $query->whereIn('id', $lpkIds))->orderBy('name')->pluck('name', 'id');

        $candidates = User::activeCandidates()
            ->candidatesOf($viewer)
            ->when($filters['lpk'] ?? null, fn (Builder $query, int|string $lpk) => $query->where('organization_id', $lpk))
            ->with(['organization', 'candidateProfile', 'skills', 'workExperiences', 'examAttempts' => fn ($query) => $query->whereNotNull('submitted_at')])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->when($filters['education_level'] ?? null, fn (Builder $query, string $level) => $query->whereRelation('candidateProfile', 'education_level', $level))
            ->when($filters['skill'] ?? null, fn (Builder $query, string $skill) => $query->whereRelation('skills', 'name', 'like', "%{$skill}%"))
            ->when($filters['min_age'] ?? null, fn (Builder $query, int|string $age) => $query->whereRelation('candidateProfile', 'birth_date', '<=', now()->subYears((int) $age)->toDateString()))
            ->when($filters['max_age'] ?? null, fn (Builder $query, int|string $age) => $query->whereRelation('candidateProfile', 'birth_date', '>', now()->subYears((int) $age + 1)->toDateString()))
            ->when(($filters['experience'] ?? null) === 'experienced', fn (Builder $query) => $query->has('workExperiences'))
            ->when(($filters['experience'] ?? null) === 'fresh', fn (Builder $query) => $query->doesntHave('workExperiences'))
            ->when(($filters['theory'] ?? null) === 'passed', fn (Builder $query) => $query->passedTheory())
            ->when(($filters['theory'] ?? null) === 'failed', fn (Builder $query) => $query
                ->whereHas('examAttempts', fn (Builder $attempts) => $attempts->where('passed', false))
                ->whereDoesntHave('examAttempts', fn (Builder $attempts) => $attempts->where('passed', true)))
            ->when(($filters['theory'] ?? null) === 'none', fn (Builder $query) => $query->whereDoesntHave('examAttempts', fn (Builder $attempts) => $attempts->whereNotNull('submitted_at')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('candidates.index', [
            'candidates' => $candidates,
            'filters' => $filters,
            'educationLevels' => EducationLevel::cases(),
            // An LPK only sees its own members, so the LPK filter is for companies and the admin.
            'lpks' => $viewer->isLpk() ? collect() : $lpks,
        ]);
    }

    /**
     * Full candidate profile with every stage result.
     */
    public function show(Request $request, User $candidate): View
    {
        abort_unless($candidate->isCandidateVisibleTo($request->user()), 404);

        $candidate->load([
            'candidateProfile', 'skills', 'workExperiences', 'documents',
            'examAttempts' => fn ($query) => $query->whereNotNull('registered_at')->with(['exam.organization', 'evaluator', 'answers.question.category']),
            'interviews.organization', 'interviews.evaluator', 'interviews.exam', 'interviews.medicalChecker',
        ]);

        $candidate->examAttempts->each->finalizeIfExpired();

        return view('candidates.show', [
            'candidate' => $candidate,
        ]);
    }
}
