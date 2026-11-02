<?php

namespace App\Http\Requests\Lpk;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreExamQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('exam'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:2000'],
            'options' => ['required', 'array', 'min:2', 'max:5'],
            'options.*' => ['nullable', 'string', 'max:255'],
            'correct_option' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Drop empty option rows and make sure the correct answer points to a filled one.
     */
    public function after(): array
    {
        return [
            function ($validator) {
                $options = $this->input('options', []);

                if (count(array_filter($options, 'filled')) < 2) {
                    $validator->errors()->add('options', 'Isi minimal 2 pilihan jawaban.');
                }

                if (blank($options[$this->integer('correct_option')] ?? null)) {
                    $validator->errors()->add('correct_option', 'Pilih jawaban benar dari pilihan yang sudah diisi.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'question' => 'pertanyaan',
            'options' => 'pilihan jawaban',
            'correct_option' => 'jawaban benar',
        ];
    }
}
