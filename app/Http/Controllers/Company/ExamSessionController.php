<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ExamSessionController extends Controller
{
    /**
     * Sessions the LPK runs for this company.
     */
    public function index(Request $request): View
    {
        $exams = Exam::query()
            ->where('company_id', $request->user()->organization_id)
            ->whereNotNull('published_at')
            ->with('organization')
            ->withCount(['participants', 'attempts as passed_count' => fn ($query) => $query->where('passed', true)])
            ->latest('starts_at')
            ->paginate(15);

        return view('company.sessions.index', ['exams' => $exams]);
    }

    /**
     * Participants, final ranking and the selection for interviews.
     */
    public function show(Exam $exam): View
    {
        Gate::authorize('follow', $exam);

        $exam->load(['organization', 'reschedules'])->loadCount('participants');

        if ($exam->hasEnded()) {
            $exam->participants()->with('exam')->get()->each->finalizeIfExpired();
        }

        return view('company.sessions.show', [
            'exam' => $exam,
            'ranking' => $exam->hasEnded() ? $exam->ranking() : collect(),
            'interviews' => $exam->interviews()->with('user')->get()->keyBy('user_id'),
            'openSeats' => $exam->openSeats(),
            'hired' => $exam->hiredCount(),
            'canSelect' => Gate::allows('select', $exam),
        ]);
    }
}
