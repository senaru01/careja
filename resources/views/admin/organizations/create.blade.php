<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Tambah LPK / Perusahaan</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <form method="POST" action="{{ route('admin.organizations.store') }}" class="grid sm:grid-cols-2 gap-4">
                    @csrf

                    <div>
                        <x-input-label for="type" value="Jenis" />
                        <x-select id="type" name="type" class="mt-1 block w-full" placeholder="Pilih" required
                            :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->label()])" :selected="old('type')" />
                        <x-input-error :messages="$errors->get('type')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="name" value="Nama LPK / Perusahaan" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="subdomain" value="Alamat (subdomain)" />
                        <div class="mt-1 flex items-center gap-2">
                            <x-text-input id="subdomain" name="subdomain" class="block w-48" :value="old('subdomain')" placeholder="oki" required />
                            <span class="text-sm text-slate-500">.{{ config('careja.root_domain') }}</span>
                        </div>
                        <x-input-error :messages="$errors->get('subdomain')" class="mt-1" />
                        <p class="text-xs text-slate-500 mt-1">Pencari kerja yang daftar lewat alamat LPK otomatis jadi member LPK itu. Staf LPK / perusahaan hanya bisa login di alamatnya sendiri.</p>
                    </div>
                    <div>
                        <x-input-label for="email" value="Email kantor (opsional)" />
                        <x-text-input id="email" type="email" name="email" class="mt-1 block w-full" :value="old('email')" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="phone" value="Telepon (opsional)" />
                        <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone')" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="address" value="Alamat (opsional)" />
                        <x-textarea id="address" name="address" class="mt-1 block w-full" rows="2">{{ old('address') }}</x-textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="description" value="Deskripsi (opsional)" />
                        <x-textarea id="description" name="description" class="mt-1 block w-full" rows="2">{{ old('description') }}</x-textarea>
                    </div>

                    <div class="sm:col-span-2 border-t pt-4 font-medium text-slate-800">Akun Login Penanggung Jawab</div>
                    <div>
                        <x-input-label for="account_name" value="Nama" />
                        <x-text-input id="account_name" name="account_name" class="mt-1 block w-full" :value="old('account_name')" required />
                        <x-input-error :messages="$errors->get('account_name')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="account_email" value="Email login (untuk kode OTP)" />
                        <x-text-input id="account_email" type="email" name="account_email" class="mt-1 block w-full" :value="old('account_email')" required />
                        <x-input-error :messages="$errors->get('account_email')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="account_password" value="Password awal" />
                        <x-text-input id="account_password" type="text" name="account_password" class="mt-1 block w-full" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('account_password')" class="mt-1" />
                        <p class="text-xs text-slate-500 mt-1">Berikan ke penanggung jawab dan minta segera diganti lewat menu Profile.</p>
                    </div>

                    <div class="sm:col-span-2 flex gap-3">
                        <x-primary-button>Simpan</x-primary-button>
                        <a href="{{ route('admin.organizations.index') }}"><x-secondary-button>Batal</x-secondary-button></a>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
