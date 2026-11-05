<?php

namespace App\Http\Requests\Company;

use App\Http\Requests\Concerns\ValidatesPositionTable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A company's recruitment request: what it needs, per position.
 */
class RecruitmentRequestRequest extends FormRequest
{
    use ValidatesPositionTable;

    /**
     * Ownership of an existing request is checked by the controller via the policy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'needed_by' => ['nullable', 'date', 'after:today'],
            ...$this->positionRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'description' => 'keterangan',
            'needed_by' => 'target mulai kerja',
            ...$this->positionAttributes(),
        ];
    }
}
