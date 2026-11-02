<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Wawancara</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <div class="panel overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Kandidat</th>
                            <th class="px-4 py-3">Sesi Tes</th>
                            <th class="px-4 py-3">Jadwal</th>
                            <th class="px-4 py-3">Tempat</th>
                            <th class="px-4 py-3">Hasil</th>
                            <th class="px-4 py-3">Nilai</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($interviews as $interview)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $interview->user->name }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $interview->exam ? $interview->exam->position.' (#'.$interview->exam_rank.')' : '-' }}</td>
                                <td class="px-4 py-3">{{ $interview->scheduled_at->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3">{{ $interview->location }}</td>
                                <td class="px-4 py-3">
                                    <x-badge :color="$interview->outcomeColor()">{{ $interview->outcomeLabel() }}</x-badge>
                                </td>
                                <td class="px-4 py-3">{{ $interview->score ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a class="text-blue-700 underline" href="{{ route('candidates.show', $interview->user) }}">{{ ! $interview->isEvaluated() ? 'Input Hasil' : ($interview->needsMedicalCheck() ? 'Input Medical' : 'Detail') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                Belum ada wawancara. Pilih kandidat dari peringkat <a class="underline" href="{{ route('company.sessions.index') }}">sesi tes</a> yang sudah selesai.
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $interviews->links() }}
        </div>
    </div>
</x-app-layout>
