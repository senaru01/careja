<?php

namespace App\Http\Requests\Company;

use App\Enums\MedicalStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordMedicalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('recordMedical', $this->route('interview'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'medical_status' => ['required', Rule::enum(MedicalStatus::class)],
            'medical_notes' => ['required', 'string', 'min:5', 'max:3000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'medical_status' => 'hasil medical',
            'medical_notes' => 'catatan medical',
        ];
    }
}
