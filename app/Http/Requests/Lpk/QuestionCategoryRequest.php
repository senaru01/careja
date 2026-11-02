<?php

namespace App\Http\Requests\Lpk;

use App\Models\QuestionCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionCategoryRequest extends FormRequest
{
    /**
     * Creating is limited to LPK staff by route middleware; updating checks ownership.
     */
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category === null || $this->user()->can('manage', $category);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique(QuestionCategory::class)
                    ->where('organization_id', $this->user()->organization_id)
                    ->ignore($this->route('category')),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama kategori',
            'description' => 'deskripsi',
        ];
    }
}
