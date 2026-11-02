<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
            Pengguna
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            @unless ($twoFactorGloballyEnabled)
                <div class="flex items-center gap-2 rounded-2xl bg-amber-50/90 px-5 py-3 text-sm text-amber-800 ring-1 ring-amber-200">
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    Verifikasi 2 langkah sedang <strong>dimatikan untuk semua pengguna</strong> lewat <code>TWO_FACTOR_ENABLED=false</code> di <code>.env</code>.
                    Pengaturan per pengguna di bawah baru berlaku setelah diaktifkan kembali.
                </div>
            @endunless

            <div class="flex gap-2">
                @foreach (['pending' => "Menunggu ({$pendingCount})", 'approved' => 'Disetujui', 'all' => 'Semua'] as $key => $label)
                    <a href="{{ route('admin.users.index', ['status' => $key]) }}"
                       class="px-4 py-2 rounded-full text-sm font-medium {{ $status === $key ? 'bg-blue-900 text-white' : 'bg-white/80 text-slate-600 hover:bg-white' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="panel overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-6 py-3">Nama</th>
                            <th class="px-6 py-3">Email</th>
                            <th class="px-6 py-3">Role</th>
                            <th class="px-6 py-3">Terdaftar</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Verifikasi 2 Langkah</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-6 py-4">{{ $user->name }}</td>
                                <td class="px-6 py-4">{{ $user->email }}</td>
                                <td class="px-6 py-4">{{ $user->role->label() }}</td>
                                <td class="px-6 py-4">{{ $user->created_at->format('d M Y H:i') }}</td>
                                <td class="px-6 py-4">
                                    @if ($user->isApproved())
                                        <span class="px-2 py-1 rounded bg-green-100 text-green-700 text-xs">Disetujui</span>
                                    @else
                                        <span class="px-2 py-1 rounded bg-yellow-100 text-yellow-700 text-xs">Menunggu</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <form method="POST" action="{{ route('admin.users.two-factor', $user) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" role="switch" aria-checked="{{ $user->two_factor_enabled ? 'true' : 'false' }}"
                                                title="{{ $user->two_factor_enabled ? 'Klik untuk mematikan' : 'Klik untuk mengaktifkan' }}"
                                                class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition {{ $user->two_factor_enabled ? 'bg-blue-600' : 'bg-slate-300' }}">
                                            <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition {{ $user->two_factor_enabled ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
                                        </button>
                                        <span class="text-xs {{ $user->two_factor_enabled && $twoFactorGloballyEnabled ? 'text-blue-700 font-semibold' : 'text-slate-500' }}">
                                            {{ $user->two_factor_enabled ? 'Aktif' : 'Mati' }}
                                        </span>
                                    </form>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        @unless ($user->isApproved())
                                            <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <x-primary-button>Setujui</x-primary-button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                                  onsubmit="return confirm('Tolak dan hapus pendaftaran ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <x-danger-button>Tolak</x-danger-button>
                                            </form>
                                        @elseif (! $user->isSuperAdmin())
                                            <form method="POST" action="{{ route('admin.users.revoke', $user) }}"
                                                  onsubmit="return confirm('Cabut akses pengguna ini?')">
                                                @csrf
                                                @method('PATCH')
                                                <x-secondary-button type="submit">Cabut Akses</x-secondary-button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-slate-500">Tidak ada pengguna.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
