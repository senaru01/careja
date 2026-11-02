<?php

namespace App\Http\Requests\Worker;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    /**
     * A candidate may upload a proof only with a complete profile, no pending
     * payment and no membership that is still active (renewing after expiry is allowed).
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user->hasCompleteCandidateProfile()
            && ! $user->hasPendingPayment()
            && ! $user->hasActiveMembership();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'proof' => [
                'required', 'file',
                'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png',
                'max:'.config('careja.max_upload_kb'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'proof' => 'bukti pembayaran',
        ];
    }
}
