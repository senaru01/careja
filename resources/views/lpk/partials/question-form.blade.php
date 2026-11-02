{{--
    Multiple-choice question form (options A–E), shared by exam questions and the question bank.
    Params: $action, $method ('POST'|'PUT'), $submitLabel, optional $question (with options loaded).
--}}
@php
    $existingOptions = isset($question) ? $question->options->values() : collect();
    $existingCorrect = $existingOptions->search(fn ($option) => $option->is_correct);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-3">
    @csrf
    @if (($method ?? 'POST') !== 'POST') @method($method) @endif

    <x-textarea name="question" class="block w-full" rows="3" placeholder="Tulis pertanyaan" required>{{ old('question', $question->question ?? '') }}</x-textarea>
    <x-input-error :messages="$errors->get('question')" />

    @foreach (range(0, 4) as $index)
        <label class="flex items-center gap-3">
            <input type="radio" name="correct_option" value="{{ $index }}" title="Tandai sebagai jawaban benar"
                   @checked((string) old('correct_option', $existingCorrect === false ? '0' : $existingCorrect) === (string) $index)
                   class="text-blue-600 focus:ring-blue-500">
            <span class="w-5 text-sm font-semibold text-slate-500">{{ chr(65 + $index) }}.</span>
            <x-text-input name="options[{{ $index }}]" class="block w-full"
                :value="old('options.'.$index, $existingOptions[$index]->text ?? '')"
                placeholder="Pilihan {{ chr(65 + $index) }}{{ $index > 1 ? ' (opsional)' : '' }}" />
        </label>
    @endforeach
    <p class="text-xs text-slate-500">Pilih bulatan di kiri untuk menandai jawaban yang benar.</p>
    <x-input-error :messages="array_merge($errors->get('options'), $errors->get('correct_option'))" />

    <x-primary-button>{{ $submitLabel }}</x-primary-button>
</form>
