<?php

namespace App\Http\Requests\Worker;

use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Models\CandidateProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCandidateProfileRequest extends FormRequest
{
    /**
     * Route middleware already limits this to job seekers.
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
            'nik' => ['required', 'digits:16', Rule::unique(CandidateProfile::class)->ignore($this->user()->id, 'user_id')],
            'phone' => ['required', 'string', 'regex:/^(\+62|0)[0-9]{8,14}$/'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:-15 years'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'education_level' => ['required', Rule::enum(EducationLevel::class)],
            'education_institution' => ['required', 'string', 'max:255'],
            'education_major' => ['nullable', 'string', 'max:255'],
            'graduation_year' => ['required', 'integer', 'min:1970', 'max:'.(now()->year + 1)],
            'summary' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nik' => 'NIK',
            'phone' => 'nomor HP',
            'gender' => 'jenis kelamin',
            'birth_place' => 'tempat lahir',
            'birth_date' => 'tanggal lahir',
            'address' => 'alamat',
            'city' => 'kota/kabupaten',
            'province' => 'provinsi',
            'education_level' => 'pendidikan terakhir',
            'education_institution' => 'nama sekolah/kampus',
            'education_major' => 'jurusan',
            'graduation_year' => 'tahun lulus',
            'summary' => 'ringkasan diri',
        ];
    }
}
