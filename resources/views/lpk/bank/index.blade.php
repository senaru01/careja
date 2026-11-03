<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Bank Soal</h2>
            <a href="{{ route('lpk.bank.template') }}"><x-secondary-button>Unduh Template Excel</x-secondary-button></a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <p class="text-sm text-slate-500 max-w-3xl">
                Kumpulkan soal per kategori di sini. Saat membuat ujian, cukup ambil sejumlah soal secara acak dari kategori,
                misalnya 50 dari 100 soal Psikotes.
            </p>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($categories as $category)
                    <div class="panel p-6 flex flex-col justify-between gap-4">
                        <div>
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    @if (auth()->user()->isSuperAdmin())
                                        <x-badge color="blue">{{ $category->organization->name }}</x-badge>
                                    @endif
                                    <h3 class="mt-1 text-lg font-semibold text-slate-900">{{ $category->name }}</h3>
                                </div>
                                <div class="text-right">
                                    <div class="text-4xl font-semibold tracking-tight text-slate-900">{{ $category->questions_count }}</div>
                                    <div class="text-xs text-slate-500">soal</div>
                                </div>
                            </div>
                            @if ($category->description)
                                <p class="mt-1 text-sm text-slate-500">{{ $category->description }}</p>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <a href="{{ route('lpk.categories.show', $category) }}"><x-primary-button type="button">Kelola Soal</x-primary-button></a>
                            <a href="{{ route('lpk.categories.edit', $category) }}" class="text-sm font-semibold text-slate-600 hover:text-blue-700">Ubah</a>
                            <form method="POST" action="{{ route('lpk.categories.destroy', $category) }}"
                                  onsubmit="return confirm('Hapus kategori {{ $category->name }} beserta {{ $category->questions_count }} soalnya? Ujian yang sudah dibuat tidak terpengaruh.')">
                                @csrf @method('DELETE')
                                <button class="text-sm font-semibold text-red-600 hover:underline">Hapus</button>
                            </form>
                        </div>
                    </div>
                @endforeach

                {{-- New category --}}
                <div class="panel p-6 border-2 border-dashed border-slate-200 !ring-0">
                    <h3 class="text-lg font-semibold text-slate-900">Kategori Baru</h3>
                    <form method="POST" action="{{ route('lpk.categories.store') }}" class="mt-3 space-y-3">
                        @csrf
                        @if (auth()->user()->isSuperAdmin())
                            <x-select name="organization_id" class="block w-full" placeholder="Pilih LPK pemilik" required
                                :options="$lpks->all()" :selected="old('organization_id', $lpks->count() === 1 ? $lpks->keys()->first() : null)" />
                            <x-input-error :messages="$errors->get('organization_id')" />
                        @endif
                        <x-text-input name="name" class="block w-full" placeholder="Nama kategori, mis. Bahasa Jepang" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" />
                        <x-textarea name="description" class="block w-full" rows="2" placeholder="Deskripsi (opsional)">{{ old('description') }}</x-textarea>
                        <x-primary-button>Tambah Kategori</x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
