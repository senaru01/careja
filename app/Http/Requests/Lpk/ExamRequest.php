<?php

namespace App\Http\Requests\Lpk;

use App\Enums\OrganizationType;
use App\Http\Requests\Concerns\ValidatesPositionTable;
use App\Models\Organization;
use App\Models\RecruitmentRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ExamRequest extends FormRequest
{
    use ValidatesPositionTable {
        after as positionTableChecks;
    }

    /**
     * Ownership of an existing exam is checked by the controller via ExamPolicy.
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
            'company_id' => ['required', 'integer', Rule::exists(Organization::class, 'id')->where('type', OrganizationType::Company->value)],
            'recruitment_request_id' => ['nullable', 'integer', Rule::exists(RecruitmentRequest::class, 'id')->whereNull('closed_at')],
            ...$this->positionRules(),
            'qualifications' => ['nullable', 'string', 'max:3000'],
            'min_participants' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:300'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            ...$this->positionTableChecks(),
            function (Validator $validator) {
                $requestId = $this->input('recruitment_request_id');

                if ($requestId && RecruitmentRequest::whereKey($requestId)->value('company_id') !== (int) $this->input('company_id')) {
                    $validator->errors()->add('company_id', 'Perusahaan harus sama dengan perusahaan yang mengirim permintaan rekrutmen.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'company_id' => 'perusahaan',
            'recruitment_request_id' => 'permintaan rekrutmen',
            ...$this->positionAttributes(),
            'qualifications' => 'kualifikasi tambahan',
            'min_participants' => 'target minimal peserta',
            'description' => 'deskripsi',
            'duration_minutes' => 'durasi',
            'passing_score' => 'nilai lulus',
            'starts_at' => 'jadwal mulai',
            'ends_at' => 'jadwal selesai',
        ];
    }
}
