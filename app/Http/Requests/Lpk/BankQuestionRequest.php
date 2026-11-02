<?php

namespace App\Http\Requests\Lpk;

/**
 * Same question/options rules as an exam question, authorized against the
 * bank category in the route instead of an exam.
 */
class BankQuestionRequest extends StoreExamQuestionRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('category'));
    }
}
