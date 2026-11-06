<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <a href="{{ route('company.recruitments.index') }}" class="text-sm text-slate-500 hover:text-blue-700">← Rekrutmen Karyawan</a>
                <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">{{ $recruitment->title }}</h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($canEdit)
                    <a href="{{ route('company.recruitments.edit', $recruitment) }}"><x-secondary-button>Ubah</x-secondary-button></a>
                    <form method="POST" action="{{ route('company.recruitments.destroy', $recruitment) }}" onsubmit="return confirm('Hapus permintaan ini?')">
                        @csrf @method('DELETE')
                        <x-danger-button>Hapus</x-danger-button>
                    </form>
                @elseif (! $recruitment->isClosed())
                    <form method="POST" action="{{ route('company.recruitments.close', $recruitment) }}" onsubmit="return confirm('Tutup permintaan ini? LPK tidak bisa membuat sesi tes baru dari permintaan ini.')">
                        @csrf @method('PATCH')
                        <x-secondary-button type="submit">Tutup Permintaan</x-secondary-button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <x-card title="Kebutuhan">
                <x-slot name="actions">
                    <x-badge :color="$recruitment->statusColor()">{{ $recruitment->statusLabel() }}</x-badge>
                </x-slot>
                <div class="mb-4 grid sm:grid-cols-3 gap-3 text-sm">
                    <div class="rounded-2xl bg-slate-50 p-3">
                        <div class="text-xs text-slate-500">Total dibutuhkan</div>
                        <div class="text-xl font-semibold text-slate-900">{{ $recruitment->totalQuota() }} <span class="text-sm font-normal text-slate-500">orang</span></div>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3">
                        <div class="text-xs text-slate-500">Target mulai kerja</div>
                        <div class="text-xl font-semibold text-slate-900">{{ $recruitment->needed_by?->translatedFormat('d M Y') ?? '-' }}</div>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3">
                        <div class="text-xs text-slate-500">Dibuat</div>
                        <div class="text-xl font-semibold text-slate-900">{{ $recruitment->created_at->translatedFormat('d M Y') }}</div>
                        <div class="text-xs text-slate-500">{{ $recruitment->creator?->name }}</div>
                    </div>
                </div>
                <div class="mb-4 text-sm">
                    <span class="text-slate-500">Dikirim ke:</span>
                    <span class="font-medium text-slate-900">{{ $recruitment->lpks->pluck('name')->implode(', ') ?: '-' }}</span>
                </div>
                <x-position-table :positions="$recruitment->positions" />
                @if ($recruitment->description)
                    <p class="mt-4 text-sm text-slate-700 whitespace-pre-line">{{ $recruitment->description }}</p>
                @endif
            </x-card>

            <x-card title="Tindak Lanjut LPK">
                @forelse ($recruitment->exams as $exam)
                    @php($phase = $exam->phase())
                    <div class="py-3 {{ $loop->first ? 'pt-0' : 'border-t border-slate-100' }} flex flex-wrap items-center justify-between gap-3 text-sm">
                        <div>
                            @if ($exam->isPublished())
                                <a class="font-semibold text-blue-700 hover:underline" href="{{ route('company.sessions.show', $exam) }}">{{ $exam->title }}</a>
                            @else
                                <span class="font-semibold text-slate-900">{{ $exam->title }}</span>
                            @endif
                            <div class="text-slate-500">{{ $exam->organization->name }} · {{ $exam->position }} · {{ $exam->starts_at?->translatedFormat('d M Y H:i') }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-badge :color="$phase->color()">{{ $phase->value === 'draft' ? 'Disiapkan LPK' : $phase->label() }}</x-badge>
                            @if ($exam->isPublished())
                                <x-badge color="blue">{{ $exam->participants_count }} siap tes</x-badge>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada sesi tes. LPK sudah menerima permintaan ini dan akan membuat jadwal tesnya.</p>
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
