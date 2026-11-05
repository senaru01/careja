{{--
    Read-only position table. $positions: ExamPosition or RecruitmentPosition models.
    $seats (optional): position id => open seats, shown as an extra column.
--}}
@props(['positions', 'seats' => null])

<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-2xl ring-1 ring-slate-200']) }}>
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-3 py-2">Posisi</th>
                <th class="px-3 py-2">Jumlah</th>
                <th class="px-3 py-2">Jenis kelamin</th>
                <th class="px-3 py-2">Pendidikan</th>
                <th class="px-3 py-2">Umur</th>
                <th class="px-3 py-2">Pengalaman</th>
                @if ($seats !== null) <th class="px-3 py-2">Kursi tersisa</th> @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($positions as $position)
                <tr>
                    <td class="px-3 py-2">
                        <div class="font-semibold text-slate-900">{{ $position->position }}</div>
                        @if ($position->notes) <div class="text-xs text-slate-500">{{ $position->notes }}</div> @endif
                    </td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ $position->quota }} orang</td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ $position->gender?->label() ?? 'Semua' }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ $position->educationLabel() }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ $position->ageLabel() }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ $position->experienceLabel() }}</td>
                    @if ($seats !== null) <td class="px-3 py-2">{{ $seats[$position->id] ?? '-' }}</td> @endif
                </tr>
            @endforeach
        </tbody>
        @if ($positions->count() > 1)
            <tfoot class="bg-slate-50 text-sm">
                <tr>
                    <td class="px-3 py-2 font-semibold text-slate-700">Total</td>
                    <td class="px-3 py-2 font-semibold text-slate-900">{{ $positions->sum('quota') }} orang</td>
                    <td colspan="{{ $seats !== null ? 5 : 4 }}"></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
