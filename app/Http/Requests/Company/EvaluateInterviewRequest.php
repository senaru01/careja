<?php

namespace App\Http\Requests\Company;

use App\Enums\InterviewStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EvaluateInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('evaluate', $this->route('interview'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(InterviewStatus::class)->except([InterviewStatus::Scheduled])],
            'score' => ['required', 'integer', 'min:0', 'max:100'],
            'evaluation' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'keputusan',
            'score' => 'nilai',
            'evaluation' => 'evaluasi',
        ];
    }
}
