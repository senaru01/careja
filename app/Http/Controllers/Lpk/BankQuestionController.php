<?php

namespace App\Http\Controllers\Lpk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lpk\BankQuestionRequest;
use App\Models\BankQuestion;
use App\Models\QuestionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BankQuestionController extends Controller
{
    public function store(BankQuestionRequest $request, QuestionCategory $category): RedirectResponse
    {
        DB::transaction(function () use ($request, $category) {
            $category->questions()
                ->create(['question' => $request->validated('question')])
                ->syncOptions($request->validated('options'), $request->integer('correct_option'));
        });

        return back()->with('status', 'Soal ditambahkan ke bank soal.');
    }

    public function edit(QuestionCategory $category, BankQuestion $question): View
    {
        Gate::authorize('manage', $category);

        return view('lpk.bank.question-edit', [
            'category' => $category,
            'question' => $question->load('options'),
        ]);
    }

    public function update(BankQuestionRequest $request, QuestionCategory $category, BankQuestion $question): RedirectResponse
    {
        DB::transaction(function () use ($request, $question) {
            $question->update(['question' => $request->validated('question')]);
            $question->syncOptions($request->validated('options'), $request->integer('correct_option'));
        });

        return redirect()->route('lpk.categories.show', $category)->with('status', 'Soal diperbarui.');
    }

    public function destroy(QuestionCategory $category, BankQuestion $question): RedirectResponse
    {
        Gate::authorize('manage', $category);

        $question->delete();

        return back()->with('status', 'Soal dihapus dari bank soal.');
    }
}
