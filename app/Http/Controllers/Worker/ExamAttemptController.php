<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ExamAttemptController extends Controller
{
    /**
     * Show the exam intro, the running exam, or its result.
     */
    public function show(ExamAttempt $attempt): View
    {
        Gate::authorize('take', $attempt);

        $attempt->load('exam.organization');
        $attempt->finalizeIfExpired();

        if ($attempt->isStarted() && ! $attempt->isSubmitted()) {
            $attempt->exam->load('questions.options');
        }

        return view('worker.attempts.show', [
            'attempt' => $attempt,
        ]);
    }

    public function start(ExamAttempt $attempt): RedirectResponse
    {
        Gate::authorize('take', $attempt);

        $attempt->load('exam');

        if (! $attempt->isStarted()) {
            if (! $attempt->canStart()) {
                return redirect()->route('worker.attempts.show', $attempt)
                    ->withErrors(['attempt' => 'Tes hanya bisa dimulai sesuai jadwal sesi: '
                        .$attempt->exam->starts_at->translatedFormat('d M Y H:i').' – '.$attempt->exam->ends_at->translatedFormat('d M Y H:i').' WIB.']);
            }

            $attempt->start();
        }

        return redirect()->route('worker.attempts.show', $attempt);
    }

    public function submit(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        Gate::authorize('take', $attempt);

        abort_unless($attempt->isStarted(), 409, 'Ujian belum dimulai.');

        $validated = $request->validate([
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'integer'],
        ]);

        $attempt->submit($validated['answers'] ?? []);

        return redirect()->route('worker.attempts.show', $attempt)
            ->with('status', 'Jawaban Anda telah dikirim dan dinilai otomatis.');
    }
}
