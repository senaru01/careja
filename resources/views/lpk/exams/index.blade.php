<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Sesi Tes</h2>
            <a href="{{ route('lpk.exams.create') }}"><x-primary-button type="button">Buat Sesi Tes</x-primary-button></a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <div class="panel overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Sesi</th>
                            <th class="px-4 py-3">Perusahaan</th>
                            <th class="px-4 py-3">Jadwal</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Soal</th>
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
                                    <div class="font-medium text-slate-900">{{ $exam->title }}</div>
                                    <div class="text-xs text-slate-500">{{ $exam->position }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $exam->company?->name ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $exam->starts_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td class="px-4 py-3"><x-badge :color="$phase->color()">{{ $phase->label() }}</x-badge></td>
                                <td class="px-4 py-3">{{ $exam->questions_count }}</td>
                                <td class="px-4 py-3">
                                    {{ $exam->participants_count }}
                                    @if ($exam->min_participants && $exam->participants_count < $exam->min_participants && $phase->value === 'open')
                                        <span class="text-xs text-amber-700">(target {{ $exam->min_participants }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $exam->passed_count }} / {{ $exam->quota ?? '-' }}</td>
                                <td class="px-4 py-3 text-right"><a class="text-blue-700 underline" href="{{ route('lpk.exams.show', $exam) }}">Kelola</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">Belum ada sesi tes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $exams->links() }}
        </div>
    </div>
</x-app-layout>
