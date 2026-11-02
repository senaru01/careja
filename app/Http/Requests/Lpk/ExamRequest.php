<?php

namespace App\Http\Requests\Lpk;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamRequest extends FormRequest
{
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
            'position' => ['required', 'string', 'max:255'],
            'qualifications' => ['required', 'string', 'min:10', 'max:3000'],
            'quota' => ['required', 'integer', 'min:1', 'max:1000'],
            'min_participants' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:300'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
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
            'position' => 'posisi / keahlian',
            'qualifications' => 'kualifikasi',
            'quota' => 'jumlah dibutuhkan',
            'min_participants' => 'target minimal peserta',
            'description' => 'deskripsi',
            'duration_minutes' => 'durasi',
            'passing_score' => 'nilai lulus',
            'starts_at' => 'jadwal mulai',
            'ends_at' => 'jadwal selesai',
        ];
    }
}
