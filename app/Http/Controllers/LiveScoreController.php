<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Live score board of a test session, for the LPK running the room and the
 * company it recruits for. Answers are saved as participants click, so the
 * score is known while they work.
 */
class LiveScoreController extends Controller
{
    public function show(Exam $exam): View
    {
        Gate::authorize('monitor', $exam);

        return view('sessions.live', [
            'exam' => $exam->load(['organization', 'company']),
            'board' => $this->board($exam),
            'canStart' => Gate::allows('start', $exam),
        ]);
    }

    public function data(Exam $exam): JsonResponse
    {
        Gate::authorize('monitor', $exam);

        return response()->json($this->board($exam));
    }

    /**
     * @return array{phase: string, phase_color: string, started: bool, ended: bool, ends_at: int|null, total_questions: int, counts: array<string, int>, participants: list<array<string, mixed>>}
     */
    private function board(Exam $exam): array
    {
        $totalQuestions = $exam->questions()->count();

        $attempts = $exam->participants()
            ->with(['user', 'position'])
            ->withCount([
                'answers as answered_count' => fn ($query) => $query->whereNotNull('exam_option_id'),
                'answers as live_correct_count' => fn ($query) => $query->where('is_correct', true),
            ])
            ->get()
            ->each->setRelation('exam', $exam);

        $attempts->each->finalizeIfExpired();

        $rows = $attempts->map(function (ExamAttempt $attempt) use ($totalQuestions) {
            $correct = $attempt->isSubmitted() ? (int) $attempt->correct_count : (int) $attempt->live_correct_count;
            $seconds = $attempt->durationSeconds();

            return [
                'id' => $attempt->id,
                'name' => $attempt->user->name,
                'position' => $attempt->position?->position,
                'status' => match (true) {
                    $attempt->isSubmitted() => 'submitted',
                    $attempt->isStarted() => 'working',
                    $attempt->isAbsent() => 'absent',
                    default => 'waiting',
                },
                'status_label' => $attempt->statusLabel(),
                'answered' => $attempt->isSubmitted() ? (int) $attempt->total_questions : (int) $attempt->answered_count,
                'correct' => $correct,
                'score' => $attempt->isSubmitted() ? (int) $attempt->score : ($totalQuestions > 0 ? (int) round($correct / $totalQuestions * 100) : 0),
                'passed' => $attempt->passed,
                'duration' => $seconds === PHP_INT_MAX ? null : sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60),
                'sort_seconds' => $seconds,
            ];
        })
            ->sortBy([['score', 'desc'], ['sort_seconds', 'asc'], ['answered', 'desc'], ['name', 'asc']])
            ->values()
            ->map(fn (array $row, int $index) => [...$row, 'rank' => $index + 1])
            ->all();

        $phase = $exam->phase();

        return [
            'phase' => $phase->label(),
            'phase_color' => $phase->color(),
            'started' => $exam->started_at !== null,
            'ended' => $exam->hasEnded(),
            'ends_at' => $exam->started_at ? $exam->ends_at?->getTimestampMs() : null,
            'total_questions' => $totalQuestions,
            'counts' => [
                'Peserta' => count($rows),
                'Mengerjakan' => collect($rows)->where('status', 'working')->count(),
                'Selesai' => collect($rows)->where('status', 'submitted')->count(),
                'Belum mulai' => collect($rows)->whereIn('status', ['waiting', 'absent'])->count(),
            ],
            'participants' => $rows,
        ];
    }
}
