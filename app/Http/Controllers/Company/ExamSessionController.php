<?php

namespace App\Http\Controllers\Company;

use App\Enums\PaymentPurpose;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\QrisPayments;
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
    public function show(Request $request, Exam $exam, QrisPayments $qris): View
    {
        Gate::authorize('follow', $exam);

        $qris->syncPendingOf($request->user());

        $exam->load(['organization', 'reschedules', 'positions', 'recruitmentRequest'])->loadCount('participants');

        if ($exam->hasEnded()) {
            $exam->participants()->with('exam')->get()->each->finalizeIfExpired();
        }

        return view('company.sessions.show', [
            'exam' => $exam,
            'rankingGroups' => $exam->hasEnded() ? $exam->rankingGroups() : collect(),
            'interviews' => $exam->interviews()->with(['user', 'position'])->get()->keyBy('user_id'),
            'openSeats' => $exam->openSeats(),
            'hired' => $exam->hiredCount(),
            'canSelect' => Gate::allows('select', $exam),
            'interviewAccessPaid' => $exam->hasPaidInterviewAccess(),
            'pendingInterviewPayment' => $exam->pendingInterviewAccessPayment(),
            'rejectedInterviewPayment' => $exam->latestRejectedPayment(PaymentPurpose::InterviewAccess),
            'qrisAvailable' => $qris->isAvailable(),
            'interviewFee' => config('careja.interview_fee'),
        ]);
    }
}
