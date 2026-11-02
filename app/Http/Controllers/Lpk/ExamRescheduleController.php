<?php

namespace App\Http\Controllers\Lpk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lpk\RescheduleExamRequest;
use App\Models\Exam;
use App\Services\ExamSessions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class ExamRescheduleController extends Controller
{
    /**
     * Postpone the session, e.g. when too few participants registered.
     */
    public function store(RescheduleExamRequest $request, Exam $exam, ExamSessions $sessions): RedirectResponse
    {
        $sessions->reschedule(
            $exam,
            $request->user(),
            Carbon::parse($request->validated('starts_at')),
            Carbon::parse($request->validated('ends_at')),
            $request->validated('reason'),
        );

        return back()->with('status', 'Jadwal dimundurkan. Peserta sudah diberi tahu dan boleh keluar sesi dengan token.');
    }
}
