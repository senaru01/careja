<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A transfer proof for a manual payment. Who may pay what is checked by the
 * controller through the policies.
 */
class PaymentProofRequest extends FormRequest
{
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
            'proof' => self::proofRules(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function proofRules(): array
    {
        return ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:'.config('careja.max_upload_kb')];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['proof' => 'bukti transfer'];
    }
}
