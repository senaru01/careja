<?php

namespace App\Http\Controllers\Lpk;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\QuestionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ExamBankQuestionController extends Controller
{
    /**
     * Copy a random selection of questions from a bank category into the exam.
     * Questions already in the exam are not picked again. Copies are used so
     * later edits to the bank never change an exam candidates already took.
     */
    public function store(Request $request, Exam $exam): RedirectResponse
    {
        Gate::authorize('update', $exam);

        $validated = $request->validateWithBag('bank', [
            'question_category_id' => [
                'required',
                Rule::exists(QuestionCategory::class, 'id')->where('organization_id', $exam->organization_id),
            ],
            'count' => ['required', 'integer', 'min:1', 'max:500'],
        ], [], ['question_category_id' => 'kategori', 'count' => 'jumlah soal']);

        $category = QuestionCategory::findOrFail($validated['question_category_id']);
        $alreadyUsed = $exam->questions()->whereNotNull('bank_question_id')->pluck('bank_question_id');

        $available = $category->questions()->whereNotIn('id', $alreadyUsed)->count();
        if ($validated['count'] > $available) {
            return back()->withErrors([
                'count' => "Kategori \"{$category->name}\" hanya memiliki {$available} soal yang belum dipakai di ujian ini.",
            ], 'bank')->withInput();
        }

        $picked = $category->questions()
            ->whereNotIn('id', $alreadyUsed)
            ->with('options')
            ->inRandomOrder()
            ->limit($validated['count'])
            ->get();

        DB::transaction(function () use ($picked, $exam, $category) {
            foreach ($picked as $bankQuestion) {
                $examQuestion = $exam->questions()->create([
                    'question' => $bankQuestion->question,
                    'question_category_id' => $category->id,
                    'bank_question_id' => $bankQuestion->id,
                ]);

                foreach ($bankQuestion->options as $option) {
                    $examQuestion->options()->create(['text' => $option->text, 'is_correct' => $option->is_correct]);
                }
            }
        });

        return back()->with('status', "{$picked->count()} soal acak dari \"{$category->name}\" ditambahkan ke ujian.");
    }

    /**
     * Remove every question from an exam that has not been assigned yet.
     */
    public function destroy(Exam $exam): RedirectResponse
    {
        Gate::authorize('update', $exam);

        ExamQuestion::where('exam_id', $exam->id)->delete();

        return back()->with('status', 'Semua soal dihapus dari ujian.');
    }
}
