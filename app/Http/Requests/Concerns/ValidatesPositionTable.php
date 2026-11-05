<?php

namespace App\Http\Requests\Concerns;

use App\Enums\EducationLevel;
use App\Enums\Gender;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Rules for the position table filled by companies (recruitment request)
 * and LPKs (test session): one row per position with its quota and who may apply.
 */
trait ValidatesPositionTable
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function positionRules(): array
    {
        return [
            'positions' => ['required', 'array', 'min:1', 'max:20'],
            'positions.*.position' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
            'positions.*.quota' => ['required', 'integer', 'min:1', 'max:1000'],
            'positions.*.gender' => ['nullable', Rule::enum(Gender::class)],
            'positions.*.min_education' => ['nullable', Rule::enum(EducationLevel::class)],
            'positions.*.min_age' => ['nullable', 'integer', 'min:15', 'max:70'],
            'positions.*.max_age' => ['nullable', 'integer', 'min:15', 'max:70'],
            'positions.*.min_experience_years' => ['required', 'integer', 'min:0', 'max:30'],
            'positions.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function positionAttributes(): array
    {
        return [
            'positions' => 'tabel posisi',
            'positions.*.position' => 'posisi',
            'positions.*.quota' => 'jumlah dibutuhkan',
            'positions.*.gender' => 'jenis kelamin',
            'positions.*.min_education' => 'pendidikan minimal',
            'positions.*.min_age' => 'umur minimal',
            'positions.*.max_age' => 'umur maksimal',
            'positions.*.min_experience_years' => 'pengalaman',
            'positions.*.notes' => 'keterangan',
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                foreach ((array) $this->input('positions') as $index => $row) {
                    $min = $row['min_age'] ?? null;
                    $max = $row['max_age'] ?? null;

                    if (is_numeric($min) && is_numeric($max) && (int) $max < (int) $min) {
                        $validator->errors()->add("positions.{$index}.max_age", 'Umur maksimal tidak boleh lebih kecil dari umur minimal.');
                    }
                }
            },
        ];
    }

    /**
     * Drop rows the user left completely empty, so an extra blank row in
     * the table does not block saving.
     */
    protected function prepareForValidation(): void
    {
        $rows = collect($this->input('positions', []))
            ->filter(fn ($row) => is_array($row) && (filled($row['position'] ?? null) || filled($row['quota'] ?? null)))
            ->map(fn (array $row) => [...$row, 'min_experience_years' => $row['min_experience_years'] ?? 0])
            ->values()
            ->all();

        $this->merge(['positions' => $rows]);
    }

    /**
     * Validated rows ready to be stored.
     *
     * @return list<array<string, mixed>>
     */
    public function positions(): array
    {
        return collect($this->validated('positions'))
            ->map(fn (array $row) => [
                'position' => trim($row['position']),
                'quota' => (int) $row['quota'],
                'gender' => $row['gender'] ?? null,
                'min_education' => $row['min_education'] ?? null,
                'min_age' => $row['min_age'] ?? null,
                'max_age' => $row['max_age'] ?? null,
                'min_experience_years' => (int) $row['min_experience_years'],
                'notes' => $row['notes'] ?? null,
            ])
            ->all();
    }
}
