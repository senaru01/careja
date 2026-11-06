<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Atur {{ $organization->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <form method="POST" action="{{ route('admin.organizations.update', $organization) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <x-card title="Profil & Alamat">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="name" value="Nama {{ $organization->type->label() }}" />
                            <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $organization->name)" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="subdomain" value="Alamat (subdomain)" />
                            <div class="mt-1 flex items-center gap-2">
                                <x-text-input id="subdomain" name="subdomain" class="block w-full" :value="old('subdomain', $organization->subdomain)" placeholder="oki" required />
                                <span class="whitespace-nowrap text-sm text-slate-500">.{{ config('careja.root_domain') }}</span>
                            </div>
                            <x-input-error :messages="$errors->get('subdomain')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="email" value="Email kantor (opsional)" />
                            <x-text-input id="email" type="email" name="email" class="mt-1 block w-full" :value="old('email', $organization->email)" />
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="phone" value="Telepon (opsional)" />
                            <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $organization->phone)" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="address" value="Alamat kantor (opsional)" />
                            <x-textarea id="address" name="address" class="mt-1 block w-full" rows="2">{{ old('address', $organization->address) }}</x-textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="description" value="Deskripsi (opsional)" />
                            <x-textarea id="description" name="description" class="mt-1 block w-full" rows="2">{{ old('description', $organization->description) }}</x-textarea>
                        </div>
                    </div>
                </x-card>

                @if ($organization->isLpk())
                    <x-card title="Biaya per Pos">
                        <p class="mb-4 text-sm text-slate-500">Kosongkan untuk memakai biaya standar Careja (ditampilkan sebagai contoh di kolom).</p>
                        <div class="grid sm:grid-cols-2 gap-4">
                            @foreach ($feeFields as $field => $purpose)
                                <div>
                                    <x-input-label :for="$field" :value="$purpose->label().' (Rp)'" />
                                    <x-text-input :id="$field" type="number" min="0" step="500" :name="$field" class="mt-1 block w-full"
                                        :value="old($field, $organization->{$field})" :placeholder="number_format($purpose->defaultFee(), 0, ',', '.')" />
                                    <x-input-error :messages="$errors->get($field)" class="mt-1" />
                                </div>
                            @endforeach
                        </div>
                    </x-card>

                    <x-card title="Pembayaran Keanggotaan Member">
                        @php($selectedCollector = old('membership_collected_by', $organization->membershipCollector()->value))
                        <div class="space-y-3" x-data="{ collector: @js($selectedCollector) }">
                            @foreach ($collectors as $collector)
                                <label class="flex gap-3 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                                    <input type="radio" name="membership_collected_by" value="{{ $collector->value }}" x-model="collector"
                                        class="mt-1 border-slate-300 text-blue-600 focus:ring-blue-500" @checked($selectedCollector === $collector->value)>
                                    <span>
                                        <span class="block font-semibold text-slate-900">{{ $collector->label() }}</span>
                                        <span class="block text-sm text-slate-500">{{ $collector->description() }}</span>
                                    </span>
                                </label>
                            @endforeach
                            <x-input-error :messages="$errors->get('membership_collected_by')" />

                            <div class="grid sm:grid-cols-3 gap-4 pt-2">
                                <div>
                                    <x-input-label for="bank_name" value="Bank LPK" />
                                    <x-text-input id="bank_name" name="bank_name" class="mt-1 block w-full" :value="old('bank_name', $organization->bank_name)" placeholder="BCA" />
                                    <x-input-error :messages="$errors->get('bank_name')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="bank_account_number" value="Nomor rekening" />
                                    <x-text-input id="bank_account_number" name="bank_account_number" class="mt-1 block w-full" :value="old('bank_account_number', $organization->bank_account_number)" />
                                    <x-input-error :messages="$errors->get('bank_account_number')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="bank_account_holder" value="Atas nama" />
                                    <x-text-input id="bank_account_holder" name="bank_account_holder" class="mt-1 block w-full" :value="old('bank_account_holder', $organization->bank_account_holder)" />
                                    <x-input-error :messages="$errors->get('bank_account_holder')" class="mt-1" />
                                </div>
                            </div>
                            <p class="text-xs text-slate-500" x-show="collector === 'lpk'">Rekening ini ditampilkan ke member saat membayar keanggotaan, dan LPK memverifikasi buktinya di menu "Pembayaran Member".</p>

                            <div class="sm:w-1/3">
                                <x-input-label for="careja_share_percent" value="Bagian Careja dari keanggotaan (%)" />
                                <x-text-input id="careja_share_percent" type="number" min="0" max="100" step="0.01" name="careja_share_percent" class="mt-1 block w-full"
                                    :value="old('careja_share_percent', $organization->careja_share_percent)" required />
                                <x-input-error :messages="$errors->get('careja_share_percent')" class="mt-1" />
                            </div>
                            <p class="text-xs text-slate-500">Isi 0 bila tidak ada potongan (biaya Careja ditutup dari biaya sesi tes). Dipakai untuk rekap bagi hasil di daftar LPK & Perusahaan.</p>
                        </div>
                    </x-card>
                @else
                    <x-card title="Terhubung ke LPK">
                        <p class="mb-4 text-sm text-slate-500">Perusahaan hanya melihat kandidat, mengirim permintaan rekrutmen, dan menerima sesi tes dari LPK yang dicentang.</p>
                        @php($checkedLpkIds = array_map('intval', old('lpk_ids', $organization->connectedLpks->modelKeys())))
                        @forelse ($lpks as $lpk)
                            <label class="mb-2 flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200">
                                <input type="checkbox" name="lpk_ids[]" value="{{ $lpk->id }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" @checked(in_array($lpk->id, $checkedLpkIds, true))>
                                <span class="font-medium text-slate-900">{{ $lpk->name }}</span>
                                <span class="text-sm text-slate-500">{{ $lpk->subdomain ? $lpk->host() : '' }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-slate-500">Belum ada LPK.</p>
                        @endforelse
                        <x-input-error :messages="array_merge($errors->get('lpk_ids'), $errors->get('lpk_ids.*'))" class="mt-1" />
                    </x-card>
                @endif

                <div class="flex gap-3">
                    <x-primary-button>Simpan</x-primary-button>
                    <a href="{{ route('admin.organizations.index') }}"><x-secondary-button>Batal</x-secondary-button></a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
