<?php

namespace App\Http\Controllers\Lpk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lpk\StoreExamQuestionRequest;
use App\Models\Exam;
use App\Models\ExamQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ExamQuestionController extends Controller
{
    public function store(StoreExamQuestionRequest $request, Exam $exam): RedirectResponse
    {
        DB::transaction(function () use ($request, $exam) {
            $question = $exam->questions()->create(['question' => $request->validated('question')]);
            $correctIndex = $request->integer('correct_option');

            foreach ($request->validated('options') as $index => $text) {
                if (filled($text)) {
                    $question->options()->create([
                        'text' => $text,
                        'is_correct' => $index === $correctIndex,
                    ]);
                }
            }
        });

        return back()->with('status', 'Soal ditambahkan.');
    }

    public function destroy(Exam $exam, ExamQuestion $question): RedirectResponse
    {
        Gate::authorize('update', $exam);

        $question->delete();

        return back()->with('status', 'Soal dihapus.');
    }
}
