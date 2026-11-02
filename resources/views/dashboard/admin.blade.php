<x-app-layout>
    <x-slot name="header">
        <x-welcome-header :user="auth()->user()" />
    </x-slot>

    <div class="pt-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <div class="grid lg:grid-cols-3 gap-6">
                <x-profile-hero :user="auth()->user()" subtitle="Pengelola Careja" chip="Super Admin" />

                <div class="lg:col-span-2 grid grid-cols-2 gap-4">
                    @foreach ($stats as $label => $value)
                        <div class="panel p-5">
                            <div class="text-sm text-slate-500">{{ $label }}</div>
                            <div class="mt-1 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                <x-card title="Pembayaran Menunggu Verifikasi">
                    <x-slot name="actions">
                        <a class="text-sm font-semibold text-blue-700 hover:underline" href="{{ route('admin.payments.index') }}">Verifikasi</a>
                    </x-slot>

                    @forelse ($pendingPayments as $payment)
                        <div class="py-3 {{ $loop->first ? 'pt-0' : 'border-t border-slate-100' }} text-sm flex justify-between">
                            <span class="font-medium text-slate-900">{{ $payment->user->name }}</span>
                            <span class="text-slate-500">{{ $payment->created_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Tidak ada pembayaran yang menunggu.</p>
                    @endforelse
                </x-card>

                <x-card title="LPK & Perusahaan">
                    <x-slot name="actions">
                        <a class="text-sm font-semibold text-blue-700 hover:underline" href="{{ route('admin.organizations.create') }}">Tambah</a>
                    </x-slot>

                    @forelse ($organizations as $organization)
                        <div class="py-3 {{ $loop->first ? 'pt-0' : 'border-t border-slate-100' }} text-sm flex justify-between items-center">
                            <span class="flex items-center gap-2 font-medium text-slate-900">{{ $organization->name }} <x-badge color="blue">{{ $organization->type->label() }}</x-badge></span>
                            <span class="text-slate-500">{{ $organization->users_count }} akun</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada LPK atau perusahaan. Tambahkan terlebih dahulu.</p>
                    @endforelse
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
