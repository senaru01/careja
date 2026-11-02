<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('lpk.categories.show', $category) }}" class="text-sm text-slate-500 hover:text-blue-700">← {{ $category->name }}</a>
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Ubah Soal</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <p class="mb-4 text-xs text-slate-500">Perubahan hanya berlaku untuk ujian yang dibuat setelah ini. Ujian yang sudah mengambil soal ini tetap memakai versi lama.</p>
                @include('lpk.partials.question-form', [
                    'action' => route('lpk.categories.questions.update', [$category, $question]),
                    'method' => 'PUT',
                    'question' => $question,
                    'submitLabel' => 'Simpan Perubahan',
                ])
            </x-card>
        </div>
    </div>
</x-app-layout>
