<?php

namespace App\Http\Controllers\Lpk;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ExamStartController extends Controller
{
    /**
     * Start the test for everyone in the room and open the live score board.
     */
    public function store(Exam $exam): RedirectResponse
    {
        Gate::authorize('start', $exam);

        $exam->startNow();

        return redirect()->route('sessions.live', $exam)
            ->with('status', 'Tes dimulai. Peserta sudah bisa mengerjakan, waktu '.$exam->duration_minutes.' menit berjalan sekarang.');
    }
}
