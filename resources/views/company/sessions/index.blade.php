<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Sesi Tes</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <div class="panel overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Posisi</th>
                            <th class="px-4 py-3">LPK</th>
                            <th class="px-4 py-3">Jadwal</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Siap Tes</th>
                            <th class="px-4 py-3">Lulus / Kuota</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($exams as $exam)
                            @php($phase = $exam->phase())
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $exam->position }}</div>
                                    <div class="text-xs text-slate-500">{{ $exam->title }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $exam->organization->name }}</td>
                                <td class="px-4 py-3">{{ $exam->starts_at?->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3"><x-badge :color="$phase->color()">{{ $phase->label() }}</x-badge></td>
                                <td class="px-4 py-3">{{ $exam->participants_count }}</td>
                                <td class="px-4 py-3">{{ $exam->passed_count }} / {{ $exam->quota }}</td>
                                <td class="px-4 py-3 text-right"><a class="text-blue-700 underline" href="{{ route('company.sessions.show', $exam) }}">{{ $phase->value === 'finished' ? 'Pilih Kandidat' : 'Detail' }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada sesi tes untuk perusahaan Anda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $exams->links() }}
        </div>
    </div>
</x-app-layout>
