<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreOrganizationRequest extends FormRequest
{
    /**
     * Route middleware already limits this to super admins.
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
            'type' => ['required', Rule::enum(OrganizationType::class)],
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => self::subdomainRules(),
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'account_password' => ['required', Password::defaults()],
        ];
    }

    /**
     * The organization's address part, "oki" in oki.careja.id: lowercase
     * letters, digits and dashes, unique, and not a reserved name.
     *
     * @return array<int, mixed>
     */
    public static function subdomainRules(?Organization $ignore = null): array
    {
        return [
            'required', 'string', 'min:2', 'max:63',
            'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/',
            Rule::notIn(config('careja.reserved_subdomains')),
            Rule::unique(Organization::class, 'subdomain')->ignore($ignore),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subdomain.regex' => 'Subdomain hanya boleh huruf kecil, angka, dan tanda strip (-), tanpa spasi.',
            'subdomain.not_in' => 'Subdomain ini dipakai sistem. Pilih nama lain.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'jenis',
            'name' => 'nama organisasi',
            'subdomain' => 'subdomain',
            'phone' => 'telepon',
            'address' => 'alamat',
            'description' => 'deskripsi',
            'account_name' => 'nama penanggung jawab',
            'account_email' => 'email login',
            'account_password' => 'password login',
        ];
    }
}
