<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Rekrutmen Karyawan</h2>
                <p class="text-sm text-slate-500">Rilis kebutuhan karyawan Anda. LPK menindaklanjuti dengan sesi tes, lalu Anda memilih kandidat dari peringkatnya.</p>
            </div>
            <a href="{{ route('company.recruitments.create') }}"><x-primary-button type="button">Buat Permintaan</x-primary-button></a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <div class="panel overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Permintaan</th>
                            <th class="px-4 py-3">Posisi</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Target kerja</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($requests as $recruitment)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $recruitment->title }}</div>
                                    <div class="text-xs text-slate-500">Dibuat {{ $recruitment->created_at->translatedFormat('d M Y') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @foreach ($recruitment->positions as $position)
                                        <div>{{ $position->position }} <span class="text-slate-500">· {{ $position->quota }} org</span></div>
                                    @endforeach
                                </td>
                                <td class="px-4 py-3">{{ $recruitment->totalQuota() }} orang</td>
                                <td class="px-4 py-3">{{ $recruitment->needed_by?->translatedFormat('d M Y') ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <x-badge :color="$recruitment->statusColor()">{{ $recruitment->statusLabel() }}</x-badge>
                                    @if ($recruitment->exams_count)
                                        <div class="mt-1 text-xs text-slate-500">{{ $recruitment->exams_count }} sesi tes</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right"><a class="text-blue-700 underline" href="{{ route('company.recruitments.show', $recruitment) }}">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada permintaan rekrutmen. Klik <strong>Buat Permintaan</strong> untuk merilis kebutuhan karyawan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $requests->links() }}
        </div>
    </div>
</x-app-layout>
