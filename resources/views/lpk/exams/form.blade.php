<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">{{ $exam->exists ? 'Ubah Sesi Tes' : 'Buat Sesi Tes' }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-2xl bg-blue-50 p-4 text-sm text-blue-900 ring-1 ring-blue-200">
                Semua yang diisi di sini diumumkan ke pencari kerja dan <strong>dikunci setelah sesi diterbitkan</strong>,
                supaya hanya yang memenuhi kualifikasi yang membayar dan ikut tes. Setelah terbit, jadwal hanya bisa dimundurkan.
            </div>

            <x-card>
                <form method="POST" action="{{ $exam->exists ? route('lpk.exams.update', $exam) : route('lpk.exams.store') }}" class="space-y-5">
                    @csrf
                    @if ($exam->exists) @method('PUT') @endif

                    <div class="text-sm font-semibold uppercase tracking-wide text-slate-400">Kebutuhan perusahaan</div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="company_id" value="Perusahaan" />
                            <x-select id="company_id" name="company_id" class="mt-1 block w-full" placeholder="Pilih perusahaan" required
                                :options="$companies" :selected="old('company_id', $exam->company_id)" />
                            <x-input-error :messages="$errors->get('company_id')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="position" value="Posisi / keahlian yang dibutuhkan" />
                            <x-text-input id="position" name="position" class="mt-1 block w-full" placeholder="mis. Operator Las (Welder)" :value="old('position', $exam->position)" required />
                            <x-input-error :messages="$errors->get('position')" class="mt-1" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="qualifications" value="Kualifikasi peserta" />
                        <x-textarea id="qualifications" name="qualifications" class="mt-1 block w-full" rows="4" required
                            placeholder="mis. Pria/Wanita 18-30 tahun, min. SMK Teknik Mesin/Las, bisa las SMAW, sehat jasmani">{{ old('qualifications', $exam->qualifications) }}</x-textarea>
                        <x-input-error :messages="$errors->get('qualifications')" class="mt-1" />
                        <p class="mt-1 text-xs text-slate-500">Pencari kerja wajib menyatakan memenuhi kualifikasi ini sebelum membayar.</p>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="quota" value="Jumlah dibutuhkan (orang)" />
                            <x-text-input id="quota" type="number" min="1" max="1000" name="quota" class="mt-1 block w-full" :value="old('quota', $exam->quota)" required />
                            <x-input-error :messages="$errors->get('quota')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="min_participants" value="Target minimal peserta (opsional)" />
                            <x-text-input id="min_participants" type="number" min="1" name="min_participants" class="mt-1 block w-full" :value="old('min_participants', $exam->min_participants)" />
                            <x-input-error :messages="$errors->get('min_participants')" class="mt-1" />
                            <p class="mt-1 text-xs text-slate-500">Jika peserta kurang dari target, Anda bisa memundurkan jadwal.</p>
                        </div>
                    </div>

                    <div class="pt-2 text-sm font-semibold uppercase tracking-wide text-slate-400">Tes</div>
                    <div>
                        <x-input-label for="title" value="Judul sesi tes" />
                        <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title', $exam->title)" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="description" value="Petunjuk tes (opsional)" />
                        <x-textarea id="description" name="description" class="mt-1 block w-full">{{ old('description', $exam->description) }}</x-textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="duration_minutes" value="Durasi (menit)" />
                            <x-text-input id="duration_minutes" type="number" min="1" max="300" name="duration_minutes" class="mt-1 block w-full" :value="old('duration_minutes', $exam->duration_minutes)" required />
                            <x-input-error :messages="$errors->get('duration_minutes')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="passing_score" value="Nilai Lulus (0-100)" />
                            <x-text-input id="passing_score" type="number" min="1" max="100" name="passing_score" class="mt-1 block w-full" :value="old('passing_score', $exam->passing_score)" required />
                            <x-input-error :messages="$errors->get('passing_score')" class="mt-1" />
                        </div>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="starts_at" value="Tes dibuka (WIB)" />
                            <x-text-input id="starts_at" type="datetime-local" name="starts_at" class="mt-1 block w-full" :value="old('starts_at', $exam->starts_at?->format('Y-m-d\TH:i'))" required />
                            <x-input-error :messages="$errors->get('starts_at')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="ends_at" value="Tes ditutup (WIB)" />
                            <x-text-input id="ends_at" type="datetime-local" name="ends_at" class="mt-1 block w-full" :value="old('ends_at', $exam->ends_at?->format('Y-m-d\TH:i'))" required />
                            <x-input-error :messages="$errors->get('ends_at')" class="mt-1" />
                        </div>
                    </div>
                    <p class="text-xs text-slate-500">Pendaftaran ditutup saat tes dibuka. Peringkat final keluar setelah tes ditutup.</p>

                    <div class="flex gap-3">
                        <x-primary-button>Simpan Draf</x-primary-button>
                        <a href="{{ $exam->exists ? route('lpk.exams.show', $exam) : route('lpk.exams.index') }}"><x-secondary-button>Batal</x-secondary-button></a>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
