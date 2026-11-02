<?php

namespace App\Http\Requests\Worker;

use App\Enums\DocumentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
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
            'type' => ['required', Rule::enum(DocumentType::class)],
            'file' => [
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
            'type' => 'jenis dokumen',
            'file' => 'berkas',
        ];
    }
}
