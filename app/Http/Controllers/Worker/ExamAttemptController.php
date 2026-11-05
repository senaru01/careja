<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use Illuminate\Http\JsonResponse;
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
            $attempt->load('answers');
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
                    ->withErrors(['attempt' => $attempt->exam->isWaitingForStart()
                        ? 'Tes belum dimulai. Tunggu pengawas LPK memulai tes di ruangan.'
                        : 'Tes hanya bisa dikerjakan saat sesi berlangsung: '
                            .$attempt->exam->starts_at->translatedFormat('d M Y H:i').' – '.$attempt->exam->ends_at->translatedFormat('d M Y H:i').' WIB.']);
            }

            $attempt->start();
        }

        return redirect()->route('worker.attempts.show', $attempt);
    }

    /**
     * Save one answer while the test runs (sent each time an option is picked).
     */
    public function answer(Request $request, ExamAttempt $attempt): JsonResponse
    {
        Gate::authorize('take', $attempt);

        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
            'option_id' => ['required', 'integer'],
        ]);

        if (! $attempt->acceptsAnswers()) {
            return response()->json(['message' => 'Waktu tes sudah habis atau jawaban sudah dikirim.'], 409);
        }

        $question = $attempt->exam->questions()->with('options')->findOrFail($validated['question_id']);
        $option = $question->options->firstWhere('id', $validated['option_id']) ?? abort(422, 'Pilihan jawaban tidak valid.');

        $attempt->saveAnswer($question, $option);

        return response()->json(['saved' => true]);
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
