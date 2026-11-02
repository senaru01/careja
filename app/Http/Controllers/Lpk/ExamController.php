<?php

namespace App\Http\Controllers\Lpk;

use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lpk\ExamRequest;
use App\Models\Exam;
use App\Models\Organization;
use App\Services\QrisPayments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

    public function create(): View
    {
        return view('lpk.exams.form', [
            'exam' => new Exam([
                'duration_minutes' => 30,
                'passing_score' => 70,
                'starts_at' => now()->addWeek()->setTime(9, 0),
                'ends_at' => now()->addWeek()->setTime(17, 0),
            ]),
            'companies' => $this->companies(),
        ]);
    }

    public function store(ExamRequest $request): RedirectResponse
    {
        $exam = $request->user()->organization->exams()->make($request->validated());
        $exam->created_by = $request->user()->id;
        $exam->save();

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
            'company',
            'questions.options', 'questions.category',
            'reschedules.rescheduler',
            'attempts' => fn ($query) => $query->with('user')->latest(),
        ]);
        $exam->attempts->each->setRelation('exam', $exam);
        $exam->attempts->each->finalizeIfExpired();

        $usedBankQuestionIds = $exam->questions->pluck('bank_question_id')->filter();

        return view('lpk.exams.show', [
            'exam' => $exam,
            'isLocked' => $exam->isLocked(),
            'pendingPayment' => $exam->pendingPublicationPayment(),
            'qrisAvailable' => $qris->isAvailable(),
            'publishCheck' => Gate::inspect('publish', $exam),
            'fee' => config('careja.exam_session_fee'),
            'participants' => $exam->attempts->filter->isRegistered(),
            'ranking' => $exam->hasEnded() ? $exam->ranking() : collect(),
            'categories' => $exam->organization->questionCategories()
                ->withCount(['questions as available_count' => fn ($query) => $query->whereNotIn('id', $usedBankQuestionIds)])
                ->get(),
            'questionsPerCategory' => $exam->questions->groupBy(fn ($question) => $question->category?->name ?? 'Soal manual')->map->count(),
        ]);
    }

    public function edit(Exam $exam): View
    {
        Gate::authorize('update', $exam);

        return view('lpk.exams.form', ['exam' => $exam, 'companies' => $this->companies()]);
    }

    public function update(ExamRequest $request, Exam $exam): RedirectResponse
    {
        Gate::authorize('update', $exam);

        $exam->update($request->validated());

        return redirect()->route('lpk.exams.show', $exam)->with('status', 'Sesi tes diperbarui.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        Gate::authorize('delete', $exam);

        $exam->delete();

        return redirect()->route('lpk.exams.index')->with('status', 'Sesi tes dihapus.');
    }

    /**
     * @return Collection<int, string>
     */
    private function companies(): Collection
    {
        return Organization::where('type', OrganizationType::Company)->orderBy('name')->pluck('name', 'id');
    }
}
