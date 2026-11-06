<?php

namespace App\Http\Requests\Company;

use App\Http\Requests\Concerns\ValidatesPositionTable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'lpk_ids' => ['required', 'array', 'min:1'],
            'lpk_ids.*' => ['integer', 'distinct', Rule::in($this->connectedLpkIds())],
            ...$this->positionRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lpk_ids.required' => 'Pilih minimal satu LPK tujuan.',
            'lpk_ids.*.in' => 'LPK tujuan harus LPK yang terhubung dengan perusahaan Anda.',
        ];
    }

    /**
     * The LPKs the company may send requests to.
     *
     * @return list<int>
     */
    public function connectedLpkIds(): array
    {
        return $this->user()->organization->connectedLpks()->pluck('organizations.id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * The chosen LPK ids.
     *
     * @return list<int>
     */
    public function lpkIds(): array
    {
        return array_map('intval', $this->validated('lpk_ids'));
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
            'lpk_ids' => 'LPK tujuan',
            ...$this->positionAttributes(),
        ];
    }
}
