<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Profil Kandidat</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            @unless ($isComplete)
                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-sm text-amber-800">
                    Lengkapi data diri, pendidikan, dan unggah <strong>CV</strong> agar dapat membayar biaya pendaftaran dan masuk daftar kandidat.
                </div>
            @endunless

            {{-- Profile photo --}}
            <x-card title="Foto Profil">
                <div class="flex flex-col sm:flex-row sm:items-center gap-6"
                     x-data="{ preview: null }">
                    <div class="shrink-0">
                        <template x-if="preview">
                            <img :src="preview" alt="Pratinjau foto" class="h-32 w-32 rounded-3xl object-cover ring-4 ring-white shadow-soft">
                        </template>
                        <div x-show="! preview">
                            <x-avatar :user="$user" size="h-32 w-32 text-3xl" class="!rounded-3xl ring-4 ring-white shadow-soft" />
                        </div>
                    </div>

                    <div class="flex-1 space-y-3">
                        <p class="text-sm text-slate-500">
                            Gunakan pas foto terbaru dengan wajah terlihat jelas. Format JPG/PNG/WEBP, minimal 200 x 200 piksel,
                            maks {{ config('careja.max_upload_kb') / 1024 }} MB. Foto hanya terlihat oleh LPK, perusahaan, dan admin.
                        </p>

                        <form method="POST" action="{{ route('worker.photo.store') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                            @csrf
                            <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" required
                                   @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                                   class="text-sm file:mr-3 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                            <x-primary-button>{{ $profile->photo_path ? 'Ganti Foto' : 'Unggah Foto' }}</x-primary-button>
                        </form>

                        @if ($profile->photo_path)
                            <form method="POST" action="{{ route('worker.photo.destroy') }}" onsubmit="return confirm('Hapus foto profil?')">
                                @csrf @method('DELETE')
                                <button class="text-xs font-semibold text-red-600 hover:underline">Hapus foto</button>
                            </form>
                        @endif

                        <x-input-error :messages="$errors->photo->all()" />
                    </div>
                </div>
            </x-card>

            {{-- Personal data & education --}}
            <x-card title="Data Diri & Pendidikan">
                <form method="POST" action="{{ route('worker.profile.update') }}" class="grid sm:grid-cols-2 gap-4">
                    @csrf
                    @method('PUT')

                    @php
                        $text = fn (string $field) => old($field, $profile->{$field});
                    @endphp

                    <div>
                        <x-input-label for="nik" value="NIK (16 digit)" />
                        <x-text-input id="nik" name="nik" class="mt-1 block w-full" :value="$text('nik')" inputmode="numeric" maxlength="16" required />
                        <x-input-error :messages="$errors->get('nik')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="phone" value="Nomor HP / WhatsApp" />
                        <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="$text('phone')" placeholder="08xxxxxxxxxx" required />
                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="gender" value="Jenis Kelamin" />
                        <x-select id="gender" name="gender" class="mt-1 block w-full" placeholder="Pilih"
                            :options="collect($genders)->mapWithKeys(fn ($gender) => [$gender->value => $gender->label()])"
                            :selected="old('gender', $profile->gender?->value)" required />
                        <x-input-error :messages="$errors->get('gender')" class="mt-1" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="birth_place" value="Tempat Lahir" />
                            <x-text-input id="birth_place" name="birth_place" class="mt-1 block w-full" :value="$text('birth_place')" required />
                            <x-input-error :messages="$errors->get('birth_place')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="birth_date" value="Tanggal Lahir" />
                            <x-text-input id="birth_date" type="date" name="birth_date" class="mt-1 block w-full" :value="old('birth_date', $profile->birth_date?->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('birth_date')" class="mt-1" />
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="address" value="Alamat Lengkap" />
                        <x-textarea id="address" name="address" class="mt-1 block w-full" rows="2" required>{{ $text('address') }}</x-textarea>
                        <x-input-error :messages="$errors->get('address')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="city" value="Kota / Kabupaten" />
                        <x-text-input id="city" name="city" class="mt-1 block w-full" :value="$text('city')" required />
                        <x-input-error :messages="$errors->get('city')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="province" value="Provinsi" />
                        <x-text-input id="province" name="province" class="mt-1 block w-full" :value="$text('province')" required />
                        <x-input-error :messages="$errors->get('province')" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2 border-t pt-4 font-medium text-slate-800">Pendidikan Terakhir</div>
                    <div>
                        <x-input-label for="education_level" value="Jenjang" />
                        <x-select id="education_level" name="education_level" class="mt-1 block w-full" placeholder="Pilih"
                            :options="collect($educationLevels)->mapWithKeys(fn ($level) => [$level->value => $level->label()])"
                            :selected="old('education_level', $profile->education_level?->value)" required />
                        <x-input-error :messages="$errors->get('education_level')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="education_institution" value="Nama Sekolah / Kampus" />
                        <x-text-input id="education_institution" name="education_institution" class="mt-1 block w-full" :value="$text('education_institution')" required />
                        <x-input-error :messages="$errors->get('education_institution')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="education_major" value="Jurusan (opsional)" />
                        <x-text-input id="education_major" name="education_major" class="mt-1 block w-full" :value="$text('education_major')" />
                        <x-input-error :messages="$errors->get('education_major')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="graduation_year" value="Tahun Lulus" />
                        <x-text-input id="graduation_year" type="number" name="graduation_year" class="mt-1 block w-full" :value="$text('graduation_year')" required />
                        <x-input-error :messages="$errors->get('graduation_year')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="summary" value="Ringkasan Diri (opsional)" />
                        <x-textarea id="summary" name="summary" class="mt-1 block w-full" rows="3">{{ $text('summary') }}</x-textarea>
                        <x-input-error :messages="$errors->get('summary')" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-primary-button>Simpan Data Diri</x-primary-button>
                    </div>
                </form>
            </x-card>

            {{-- Skills --}}
            <x-card title="Keahlian / Skill">
                <ul class="divide-y mb-4">
                    @forelse ($user->skills as $skill)
                        <li class="py-2 flex justify-between items-center text-sm">
                            <span>{{ $skill->name }} <x-badge color="indigo">{{ $skill->level->label() }}</x-badge></span>
                            <form method="POST" action="{{ route('worker.skills.destroy', $skill) }}">
                                @csrf @method('DELETE')
                                <button class="text-red-600 text-xs underline">Hapus</button>
                            </form>
                        </li>
                    @empty
                        <li class="py-2 text-sm text-slate-500">Belum ada skill.</li>
                    @endforelse
                </ul>

                <form method="POST" action="{{ route('worker.skills.store') }}" class="flex flex-wrap gap-3 items-end">
                    @csrf
                    <div class="flex-1 min-w-48">
                        <x-input-label for="skill_name" value="Nama Skill" />
                        <x-text-input id="skill_name" name="name" class="mt-1 block w-full" placeholder="mis. Pengelasan, Bahasa Jepang" required />
                    </div>
                    <div>
                        <x-input-label for="skill_level" value="Tingkat" />
                        <x-select id="skill_level" name="level" class="mt-1"
                            :options="collect($skillLevels)->mapWithKeys(fn ($level) => [$level->value => $level->label()])" />
                    </div>
                    <x-primary-button>Tambah</x-primary-button>
                </form>
                <x-input-error :messages="$errors->skill->all()" class="mt-2" />
            </x-card>

            {{-- Work experience --}}
            <x-card title="Pengalaman Kerja">
                <ul class="divide-y mb-4">
                    @forelse ($user->workExperiences as $experience)
                        <li class="py-3 flex justify-between gap-4 text-sm">
                            <div>
                                <div class="font-medium">{{ $experience->position }} — {{ $experience->company_name }}</div>
                                <div class="text-slate-500">{{ $experience->start_date->format('M Y') }} – {{ $experience->end_date?->format('M Y') ?? 'Sekarang' }}</div>
                                @if ($experience->description)
                                    <div class="text-slate-600">{{ $experience->description }}</div>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('worker.experiences.destroy', $experience) }}">
                                @csrf @method('DELETE')
                                <button class="text-red-600 text-xs underline">Hapus</button>
                            </form>
                        </li>
                    @empty
                        <li class="py-2 text-sm text-slate-500">Belum ada pengalaman kerja (boleh dikosongkan untuk fresh graduate).</li>
                    @endforelse
                </ul>

                <form method="POST" action="{{ route('worker.experiences.store') }}" class="grid sm:grid-cols-2 gap-3">
                    @csrf
                    <div>
                        <x-input-label for="company_name" value="Nama Perusahaan" />
                        <x-text-input id="company_name" name="company_name" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <x-input-label for="position" value="Jabatan" />
                        <x-text-input id="position" name="position" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <x-input-label for="start_date" value="Mulai" />
                        <x-text-input id="start_date" type="date" name="start_date" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <x-input-label for="end_date" value="Selesai (kosongkan jika masih bekerja)" />
                        <x-text-input id="end_date" type="date" name="end_date" class="mt-1 block w-full" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="description" value="Deskripsi Pekerjaan (opsional)" />
                        <x-textarea id="description" name="description" class="mt-1 block w-full" rows="2"></x-textarea>
                    </div>
                    <div class="sm:col-span-2"><x-primary-button>Tambah Pengalaman</x-primary-button></div>
                </form>
                <x-input-error :messages="$errors->experience->all()" class="mt-2" />
            </x-card>

            {{-- Documents --}}
            <x-card title="Dokumen (CV, KTP, Ijazah, Sertifikat)">
                <ul class="divide-y mb-4">
                    @forelse ($user->documents as $document)
                        <li class="py-2 flex justify-between items-center text-sm">
                            <span>
                                <x-badge color="blue">{{ $document->type->label() }}</x-badge>
                                <a class="text-blue-700 underline" href="{{ route('documents.show', $document) }}" target="_blank">{{ $document->original_name }}</a>
                            </span>
                            <form method="POST" action="{{ route('worker.documents.destroy', $document) }}">
                                @csrf @method('DELETE')
                                <button class="text-red-600 text-xs underline">Hapus</button>
                            </form>
                        </li>
                    @empty
                        <li class="py-2 text-sm text-slate-500">Belum ada dokumen. CV wajib diunggah.</li>
                    @endforelse
                </ul>

                <form method="POST" action="{{ route('worker.documents.store') }}" enctype="multipart/form-data" class="flex flex-wrap gap-3 items-end">
                    @csrf
                    <div>
                        <x-input-label for="type" value="Jenis Dokumen" />
                        <x-select id="type" name="type" class="mt-1"
                            :options="collect($documentTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])" :selected="old('type')" />
                    </div>
                    <div class="flex-1 min-w-48">
                        <x-input-label for="file" value="Berkas (PDF/JPG/PNG, maks {{ config('careja.max_upload_kb') / 1024 }} MB)" />
                        <input id="file" type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm" required>
                    </div>
                    <x-primary-button>Unggah</x-primary-button>
                </form>
                <x-input-error :messages="$errors->get('file')" class="mt-2" />
                <x-input-error :messages="$errors->get('type')" class="mt-2" />
            </x-card>
        </div>
    </div>
</x-app-layout>
