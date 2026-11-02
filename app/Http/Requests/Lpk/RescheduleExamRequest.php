<?php

namespace App\Http\Requests\Lpk;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RescheduleExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reschedule', $this->route('exam'));
    }

    /**
     * The new start must be later than the current one: a session can only be postponed.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date', 'after:'.$this->route('exam')->starts_at->toDateTimeString()],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'starts_at.after' => 'Jadwal baru harus setelah jadwal sekarang (hanya bisa dimundurkan).',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'starts_at' => 'jadwal mulai baru',
            'ends_at' => 'jadwal selesai baru',
            'reason' => 'alasan',
        ];
    }
}
