<?php

namespace App\Http\Controllers\Lpk;

use App\Enums\PaymentPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lpk\ExamRequest;
use App\Models\Exam;
use App\Models\Organization;
use App\Models\RecruitmentRequest;
use App\Services\QrisPayments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(Request $request): View
    {
        $exams = $request->user()->organization->exams()
            ->with('company')
            ->withCount(['questions', 'participants', 'attempts as passed_count' => fn ($query) => $query->where('passed', true)])
            ->latest()
            ->paginate(15);

        return view('lpk.exams.index', ['exams' => $exams]);
    }

    /**
     * New session, optionally pre-filled from a company's recruitment
     * request (?permintaan=id): company and position table are copied, the
     * LPK adds the test details.
     */
    public function create(Request $request): View
    {
        $recruitment = $request->filled('permintaan')
            ? RecruitmentRequest::with(['company', 'positions'])->findOrFail($request->integer('permintaan'))
            : null;

        if ($recruitment) {
            Gate::authorize('followUp', $recruitment);
        }

        return view('lpk.exams.form', [
            'exam' => new Exam([
                'company_id' => $recruitment?->company_id,
                'recruitment_request_id' => $recruitment?->id,
                'title' => $recruitment ? 'Tes '.$recruitment->title : null,
                'duration_minutes' => 30,
                'passing_score' => 70,
                'starts_at' => now()->addWeek()->setTime(9, 0),
                'ends_at' => now()->addWeek()->setTime(17, 0),
            ]),
            'positions' => $recruitment?->positions ?? collect(),
            'recruitment' => $recruitment,
            'companies' => $this->companies($request->user()->organization),
        ]);
    }

    public function store(ExamRequest $request): RedirectResponse
    {
        $exam = DB::transaction(function () use ($request) {
            $exam = $request->user()->organization->exams()->make($request->safe()->except('positions'));
            $exam->created_by = $request->user()->id;
            $exam->save();
            $exam->syncPositions($request->positions());

            return $exam;
        });

        return redirect()->route('lpk.exams.show', $exam)
            ->with('status', 'Sesi tes dibuat sebagai draf. Tambahkan soal, lalu terbitkan.');
    }

    public function show(Exam $exam, QrisPayments $qris): View
    {
        Gate::authorize('view', $exam);

        if ($pending = $exam->pendingPublicationPayment()) {
            $qris->sync($pending);
            $exam->refresh();
        }

        $exam->load([
            'company', 'positions', 'recruitmentRequest',
            'questions.options', 'questions.category',
            'reschedules.rescheduler',
            'attempts' => fn ($query) => $query->with(['user', 'position'])->latest(),
        ]);
        $exam->attempts->each->setRelation('exam', $exam);
        $exam->attempts->each->finalizeIfExpired();

        $usedBankQuestionIds = $exam->questions->pluck('bank_question_id')->filter();

        return view('lpk.exams.show', [
            'exam' => $exam,
            'isLocked' => $exam->isLocked(),
            'pendingPayment' => $exam->pendingPublicationPayment(),
            'rejectedPayment' => $exam->isPublished() ? null : $exam->latestRejectedPayment(PaymentPurpose::ExamSession),
            'qrisAvailable' => $qris->isAvailable(),
            'publishCheck' => Gate::inspect('publish', $exam),
            'fee' => PaymentPurpose::ExamSession->fee($exam->organization),
            'participants' => $exam->attempts->filter->isRegistered(),
            'rankingGroups' => $exam->hasEnded() ? $exam->rankingGroups() : collect(),
            'startCheck' => Gate::inspect('start', $exam),
            'categories' => $exam->organization->questionCategories()
                ->withCount(['questions as available_count' => fn ($query) => $query->whereNotIn('id', $usedBankQuestionIds)])
                ->get(),
            'questionsPerCategory' => $exam->questions->groupBy(fn ($question) => $question->category?->name ?? 'Soal manual')->map->count(),
        ]);
    }

    public function edit(Exam $exam): View
    {
        Gate::authorize('update', $exam);

        return view('lpk.exams.form', [
            'exam' => $exam,
            'positions' => $exam->positions,
            'recruitment' => $exam->recruitmentRequest,
            'companies' => $this->companies($exam->organization),
        ]);
    }

    public function update(ExamRequest $request, Exam $exam): RedirectResponse
    {
        Gate::authorize('update', $exam);

        DB::transaction(function () use ($request, $exam) {
            $exam->update($request->safe()->except('positions'));
            $exam->syncPositions($request->positions());
        });

        return redirect()->route('lpk.exams.show', $exam)->with('status', 'Sesi tes diperbarui.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        Gate::authorize('delete', $exam);

        $exam->delete();

        return redirect()->route('lpk.exams.index')->with('status', 'Sesi tes dihapus.');
    }

    /**
     * The companies this LPK is connected to: the only ones it can run sessions for.
     *
     * @return Collection<int, string>
     */
    private function companies(Organization $lpk): Collection
    {
        return $lpk->connectedCompanies()->pluck('name', 'organizations.id');
    }
}
