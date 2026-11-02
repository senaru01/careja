<?php

namespace App\Http\Controllers\Lpk;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AttemptEvaluationController extends Controller
{
    /**
     * Save the LPK's written evaluation of a graded attempt, used by the
     * candidate as guidance to improve their skills.
     */
    public function update(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        Gate::authorize('evaluate', $attempt);

        $validated = $request->validateWithBag('evaluation'.$attempt->id, [
            'evaluation' => ['required', 'string', 'min:10', 'max:5000'],
        ], [], ['evaluation' => 'evaluasi']);

        $attempt->evaluate($request->user(), $validated['evaluation']);

        return back()->with('status', 'Evaluasi tes teori disimpan.');
    }
}
