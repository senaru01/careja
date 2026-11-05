<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-end gap-3">
            <div>
                <a href="{{ route('lpk.categories.index') }}" class="text-sm text-slate-500 hover:text-blue-700">← Bank Soal</a>
                <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">{{ $category->name }}</h2>
            </div>
            <div class="text-right">
                <div class="text-4xl font-semibold tracking-tight text-slate-900">{{ $category->questions_count }}</div>
                <div class="text-xs text-slate-500">soal di kategori ini</div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-3"><x-flash /></div>

            {{-- Left: import & add --}}
            <div class="space-y-6">
                <x-card title="Export & Import Excel">
                    <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                        <div class="font-semibold text-slate-900">Koreksi & tinjau soal di Excel</div>
                        <ol class="mt-1 list-decimal ms-4 space-y-0.5">
                            <li>Export semua soal kategori ini.</li>
                            <li>Perbaiki pertanyaan, pilihan, atau kunci jawaban di Excel. Kolom <strong>ID</strong> jangan diubah.</li>
                            <li>Import kembali file tersebut: soal ber-ID diperbarui, baris tanpa ID ditambahkan sebagai soal baru.</li>
                        </ol>
                        <a href="{{ route('lpk.categories.export', $category) }}" class="mt-3 inline-block"><x-secondary-button>Export Excel ({{ $category->questions_count }} soal)</x-secondary-button></a>
                    </div>
                    <p class="mt-4 text-sm text-slate-500">
                        Soal baru: gunakan <a href="{{ route('lpk.bank.template') }}" class="font-semibold text-blue-700 hover:underline">template Excel</a>.
                        Kolom: Pertanyaan, Pilihan A–E, dan Jawaban (huruf A–E). Soal baru yang sama persis dengan yang sudah ada akan dilewati.
                    </p>
                    <form method="POST" action="{{ route('lpk.categories.import', $category) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                        @csrf
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                               class="block w-full text-sm file:mr-3 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                        @if ($errors->import->any())
                            <div class="rounded-2xl bg-red-50 p-3 text-xs text-red-700 ring-1 ring-red-200">
                                <div class="font-semibold">Import dibatalkan, tidak ada soal yang disimpan atau diubah:</div>
                                <ul class="mt-1 list-disc ms-4 space-y-0.5">
                                    @foreach ($errors->import->all() as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <x-primary-button>Import Soal</x-primary-button>
                    </form>
                </x-card>

                <x-card title="Tambah Soal Manual">
                    @include('lpk.partials.question-form', [
                        'action' => route('lpk.categories.questions.store', $category),
                        'submitLabel' => 'Tambah ke Bank Soal',
                    ])
                </x-card>
            </div>

            {{-- Right: question list --}}
            <div class="lg:col-span-2 panel">
                <div class="px-6 pt-6 flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-slate-900">Daftar Soal</h3>
                    <form method="GET" class="flex gap-2">
                        <x-text-input name="search" :value="$search" placeholder="Cari pertanyaan..." class="w-56" />
                        <x-secondary-button type="submit">Cari</x-secondary-button>
                    </form>
                </div>

                <div class="p-6">
                    @forelse ($questions as $question)
                        <div class="py-4 {{ $loop->first ? 'pt-0' : 'border-t border-slate-100' }} text-sm">
                            <div class="flex justify-between gap-4">
                                <div class="font-medium text-slate-900">
                                    {{ $questions->firstItem() + $loop->index }}. {{ $question->question }}
                                </div>
                                <div class="flex shrink-0 gap-3">
                                    <a href="{{ route('lpk.categories.questions.edit', [$category, $question]) }}" class="text-xs font-semibold text-blue-700 hover:underline">Ubah</a>
                                    <form method="POST" action="{{ route('lpk.categories.questions.destroy', [$category, $question]) }}" onsubmit="return confirm('Hapus soal ini?')">
                                        @csrf @method('DELETE')
                                        <button class="text-xs font-semibold text-red-600 hover:underline">Hapus</button>
                                    </form>
                                </div>
                            </div>
                            <ol class="mt-2 grid sm:grid-cols-2 gap-x-6 gap-y-1">
                                @foreach ($question->options as $option)
                                    <li class="{{ $option->is_correct ? 'font-semibold text-emerald-700' : 'text-slate-600' }}">
                                        {{ chr(65 + $loop->index) }}. {{ $option->text }} @if ($option->is_correct) ✓ @endif
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ $search !== '' ? 'Tidak ada soal yang cocok.' : 'Belum ada soal. Import dari Excel atau tambah manual.' }}</p>
                    @endforelse

                    <div class="mt-4">{{ $questions->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
