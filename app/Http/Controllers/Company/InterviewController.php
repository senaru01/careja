<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\EvaluateInterviewRequest;
use App\Http\Requests\Company\RecordMedicalRequest;
use App\Http\Requests\Company\StoreInterviewRequest;
use App\Models\Exam;
use App\Models\Interview;
use App\Models\User;
use App\Notifications\InterviewScheduled;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class InterviewController extends Controller
{
    public function index(Request $request): View
    {
        $interviews = $request->user()->organization->interviews()
            ->with(['user', 'evaluator', 'exam'])
            ->orderByRaw("case when status = 'scheduled' then 0 when status = 'passed' and medical_status is null then 1 else 2 end")
            ->orderBy('scheduled_at')
            ->paginate(20);

        return view('company.interviews.index', ['interviews' => $interviews]);
    }

    /**
     * Invite candidates picked from a finished session's ranking.
     */
    public function store(StoreInterviewRequest $request, Exam $exam): RedirectResponse
    {
        $ranks = $request->ranking()->pluck('rank', 'user_id');
        $skipped = $request->skippedRanks();
        $skipReason = $skipped->isNotEmpty()
            ? 'Melewati peringkat '.$skipped->implode(', ').': '.trim($request->validated('skip_reason'))
            : null;

        $interviews = DB::transaction(fn () => collect($request->validated('user_ids'))->map(function ($userId) use ($request, $exam, $ranks, $skipReason) {
            $interview = $request->user()->organization->interviews()->make($request->safe()->only(['scheduled_at', 'location', 'notes']));
            $interview->forceFill([
                'user_id' => (int) $userId,
                'exam_id' => $exam->id,
                'exam_rank' => $ranks[(int) $userId] ?? null,
                'skip_reason' => $skipReason,
                'scheduled_by' => $request->user()->id,
            ])->save();

            return $interview;
        }));

        foreach ($interviews as $interview) {
            Notification::send(User::find($interview->user_id), new InterviewScheduled($interview));
        }

        return back()->with('status', "Undangan wawancara terkirim ke {$interviews->count()} kandidat.");
    }

    /**
     * Record the interview decision and evaluation (once).
     */
    public function update(EvaluateInterviewRequest $request, Interview $interview): RedirectResponse
    {
        $interview->forceFill([
            ...$request->validated(),
            'evaluated_by' => $request->user()->id,
            'evaluated_at' => now(),
        ])->save();

        return back()->with('status', 'Hasil wawancara disimpan.');
    }

    /**
     * Record the final medical checkup (once). An unfit result frees the seat
     * so a replacement can be picked from the same session's ranking.
     */
    public function medical(RecordMedicalRequest $request, Interview $interview): RedirectResponse
    {
        $interview->forceFill([
            ...$request->validated(),
            'medical_checked_by' => $request->user()->id,
            'medical_checked_at' => now(),
        ])->save();

        return back()->with('status', $interview->medical_status->value === 'unfit'
            ? 'Hasil medical disimpan. Satu kursi kuota terbuka kembali, Anda bisa memilih pengganti dari peringkat sesi tes.'
            : 'Hasil medical disimpan. Kandidat diterima.');
    }
}
