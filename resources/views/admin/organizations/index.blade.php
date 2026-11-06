<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">LPK & Perusahaan</h2>
            <a href="{{ route('admin.organizations.create') }}"><x-primary-button type="button">Tambah</x-primary-button></a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <div class="grid md:grid-cols-2 gap-6">
                @forelse ($organizations as $organization)
                    <x-card :title="$organization->name">
                        <x-slot name="actions">
                            <div class="flex items-center gap-3">
                                <x-badge color="indigo">{{ $organization->type->label() }}</x-badge>
                                <a href="{{ route('admin.organizations.edit', $organization) }}" class="text-sm font-semibold text-blue-700 hover:underline">Atur</a>
                            </div>
                        </x-slot>

                        <div class="mb-3 text-sm">
                            @if ($organization->subdomain)
                                <a href="{{ $organization->url() }}" target="_blank" class="font-semibold text-blue-700 hover:underline">{{ $organization->host() }}</a>
                            @else
                                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">Subdomain belum diatur — belum bisa login / daftar</span>
                            @endif
                        </div>

                        <dl class="text-sm space-y-1">
                            <div>{{ $organization->address ?: '-' }}</div>
                            <div class="text-slate-500">{{ $organization->email }} {{ $organization->phone ? '· '.$organization->phone : '' }}</div>
                        </dl>

                        @if ($organization->isLpk())
                            @php($revenue = (int) ($membershipRevenue[$organization->id] ?? 0))
                            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                <div class="rounded-2xl bg-slate-50 p-3">
                                    <div class="text-xs text-slate-500">Member</div>
                                    <div class="text-lg font-semibold text-slate-900">{{ $organization->members_count }}</div>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3">
                                    <div class="text-xs text-slate-500">Keanggotaan</div>
                                    <div class="text-lg font-semibold text-slate-900">Rp {{ number_format($organization->feeFor(\App\Enums\PaymentPurpose::Membership), 0, ',', '.') }}</div>
                                    <div class="text-xs text-slate-500">{{ $organization->membershipCollector()->label() }}</div>
                                </div>
                                <div class="col-span-2 rounded-2xl bg-slate-50 p-3">
                                    <div class="text-xs text-slate-500">Keanggotaan terbayar · bagian Careja {{ rtrim(rtrim(number_format($organization->careja_share_percent, 2, ',', '.'), '0'), ',') }}%</div>
                                    <div class="font-semibold text-slate-900">
                                        Rp {{ number_format($revenue, 0, ',', '.') }}
                                        <span class="font-normal text-slate-500">· Careja Rp {{ number_format($organization->carejaShareOf($revenue), 0, ',', '.') }} · LPK Rp {{ number_format($revenue - $organization->carejaShareOf($revenue), 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-sm text-slate-600">
                                <span class="font-medium text-slate-700">Perusahaan terhubung:</span>
                                {{ $organization->connectedCompanies->pluck('name')->implode(', ') ?: '-' }}
                            </div>
                        @else
                            <div class="mt-3 text-sm text-slate-600">
                                <span class="font-medium text-slate-700">Terhubung ke LPK:</span>
                                {{ $organization->connectedLpks->pluck('name')->implode(', ') ?: 'belum ada' }}
                            </div>
                        @endif

                        <div class="mt-4 text-sm">
                            <div class="font-medium text-slate-700">Akun login</div>
                            <ul class="text-slate-600">
                                @foreach ($organization->staff as $account)
                                    <li>{{ $account->name }} — {{ $account->email }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </x-card>
                @empty
                    <p class="text-sm text-slate-500">Belum ada LPK atau perusahaan.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
