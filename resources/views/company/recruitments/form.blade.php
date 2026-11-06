<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">{{ $recruitment->exists ? 'Ubah Permintaan Rekrutmen' : 'Buat Permintaan Rekrutmen' }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-2xl bg-blue-50 p-4 text-sm text-blue-900 ring-1 ring-blue-200">
                Permintaan ini dikirim ke LPK. LPK membuat sesi tes dari tabel posisi Anda, lalu pencari kerja yang memenuhi syarat
                mendaftar dan ikut tes. Permintaan masih bisa diubah sampai LPK membuat sesi tesnya.
            </div>

            <x-card>
                <form method="POST" action="{{ $recruitment->exists ? route('company.recruitments.update', $recruitment) : route('company.recruitments.store') }}" class="space-y-5">
                    @csrf
                    @if ($recruitment->exists) @method('PUT') @endif

                    <div class="grid sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <x-input-label for="title" value="Judul permintaan" />
                            <x-text-input id="title" name="title" class="mt-1 block w-full" placeholder="mis. Rekrutmen Produksi Desember 2026" :value="old('title', $recruitment->title)" required />
                            <x-input-error :messages="$errors->get('title')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="needed_by" value="Target mulai kerja (opsional)" />
                            <x-text-input id="needed_by" type="date" name="needed_by" class="mt-1 block w-full" :value="old('needed_by', $recruitment->needed_by?->format('Y-m-d'))" />
                            <x-input-error :messages="$errors->get('needed_by')" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <x-input-label value="Posisi yang dibutuhkan" />
                        <p class="mb-2 text-xs text-slate-500">Satu baris per posisi, mis. Operator 2 orang, Staff QC 1 orang, Admin Produksi 1 orang.</p>
                        <x-position-table-input :positions="$recruitment->positions ?? []" />
                    </div>

                    <div>
                        <x-input-label value="Kirim ke LPK" />
                        @if ($connectedLpks->isEmpty())
                            <p class="mt-1 rounded-2xl bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-200">Perusahaan Anda belum terhubung ke LPK mana pun. Hubungi admin Careja untuk menghubungkan LPK.</p>
                        @else
                            <p class="mb-2 text-xs text-slate-500">Hanya LPK yang dicentang yang menerima permintaan ini.</p>
                            @php($checkedLpkIds = array_map('intval', old('lpk_ids', $selectedLpkIds)))
                            <div class="flex flex-wrap gap-3">
                                @foreach ($connectedLpks as $lpk)
                                    <label class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm ring-1 ring-slate-200">
                                        <input type="checkbox" name="lpk_ids[]" value="{{ $lpk->id }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" @checked(in_array($lpk->id, $checkedLpkIds, true))>
                                        {{ $lpk->name }}
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        <x-input-error :messages="array_merge($errors->get('lpk_ids'), $errors->get('lpk_ids.*'))" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="description" value="Keterangan untuk LPK (opsional)" />
                        <x-textarea id="description" name="description" class="mt-1 block w-full" rows="3"
                            placeholder="mis. lokasi kerja, sistem shift, kualifikasi tambahan seperti sehat jasmani dan tidak buta warna">{{ old('description', $recruitment->description) }}</x-textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    <div class="flex gap-3">
                        <x-primary-button>{{ $recruitment->exists ? 'Simpan Perubahan' : 'Kirim ke LPK' }}</x-primary-button>
                        <a href="{{ $recruitment->exists ? route('company.recruitments.show', $recruitment) : route('company.recruitments.index') }}"><x-secondary-button>Batal</x-secondary-button></a>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
