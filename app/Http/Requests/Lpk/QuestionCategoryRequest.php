<?php

namespace App\Http\Requests\Lpk;

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\QuestionCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionCategoryRequest extends FormRequest
{
    /**
     * Creating is limited to LPK staff and the super admin by route
     * middleware; updating checks ownership.
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
        $choosesLpk = $this->user()->isSuperAdmin() && $this->route('category') === null;

        return [
            'organization_id' => $choosesLpk
                ? ['required', 'integer', Rule::exists(Organization::class, 'id')->where('type', OrganizationType::Lpk->value)]
                : ['prohibited'],
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique(QuestionCategory::class)
                    ->where('organization_id', $this->owningOrganizationId())
                    ->ignore($this->route('category')),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * The LPK the category belongs to: the edited category's LPK, the LPK the
     * super admin picked, or the LPK of the staff member.
     */
    public function owningOrganizationId(): ?int
    {
        return $this->route('category')?->organization_id
            ?? ($this->user()->isSuperAdmin() ? $this->integer('organization_id') : $this->user()->organization_id);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'organization_id' => 'LPK',
            'name' => 'nama kategori',
            'description' => 'deskripsi',
        ];
    }
}
