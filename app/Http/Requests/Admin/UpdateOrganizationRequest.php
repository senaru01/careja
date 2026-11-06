<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentCollector;
use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Settings of an LPK or company: profile and subdomain; for an LPK also its
 * fees per post and where its members pay the membership; for a company the
 * LPKs it is connected to.
 */
class UpdateOrganizationRequest extends FormRequest
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
        $organization = $this->organization();
        $fee = ['nullable', 'integer', 'min:0', 'max:100000000'];

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => StoreOrganizationRequest::subdomainRules($organization),
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];

        if ($organization->isLpk()) {
            return [
                ...$rules,
                'membership_fee' => $fee,
                'exam_session_fee' => $fee,
                'exam_registration_fee' => $fee,
                'interview_fee' => $fee,
                'medical_checkup_fee' => $fee,
                'membership_collected_by' => ['required', Rule::enum(PaymentCollector::class)],
                'careja_share_percent' => ['required', 'numeric', 'min:0', 'max:100'],
                'bank_name' => ['nullable', 'required_if:membership_collected_by,'.PaymentCollector::Lpk->value, 'string', 'max:100'],
                'bank_account_number' => ['nullable', 'required_if:membership_collected_by,'.PaymentCollector::Lpk->value, 'string', 'max:50'],
                'bank_account_holder' => ['nullable', 'required_if:membership_collected_by,'.PaymentCollector::Lpk->value, 'string', 'max:255'],
            ];
        }

        return [
            ...$rules,
            'lpk_ids' => ['nullable', 'array'],
            'lpk_ids.*' => ['integer', 'distinct', Rule::exists(Organization::class, 'id')->where('type', 'lpk')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...(new StoreOrganizationRequest)->messages(),
            'bank_name.required_if' => 'Isi nama bank bila member membayar langsung ke rekening LPK.',
            'bank_account_number.required_if' => 'Isi nomor rekening bila member membayar langsung ke rekening LPK.',
            'bank_account_holder.required_if' => 'Isi nama pemilik rekening bila member membayar langsung ke rekening LPK.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama organisasi',
            'phone' => 'telepon',
            'address' => 'alamat',
            'description' => 'deskripsi',
            'membership_fee' => 'biaya keanggotaan',
            'exam_session_fee' => 'biaya terbit sesi tes',
            'exam_registration_fee' => 'biaya ikut sesi tes',
            'interview_fee' => 'biaya pengambilan kandidat',
            'medical_checkup_fee' => 'biaya medical checkup',
            'membership_collected_by' => 'penerima pembayaran keanggotaan',
            'careja_share_percent' => 'bagian Careja',
            'bank_name' => 'nama bank',
            'bank_account_number' => 'nomor rekening',
            'bank_account_holder' => 'nama pemilik rekening',
        ];
    }

    public function organization(): Organization
    {
        return $this->route('organization');
    }
}
