{{--
    Final ranking of a finished session (passed participants only).
    $interviews: interviews of the session keyed by user_id.
    mask: hide names (for job seekers); highlight: user id to emphasise.
    selectable: show checkboxes named user_ids[] (company selection form); preselect: ids checked by default.
--}}
@props(['ranking', 'interviews', 'quota' => null, 'mask' => false, 'highlight' => null, 'selectable' => false, 'preselect' => []])

@php
    $maskName = fn (string $name) => collect(preg_split('/\s+/', trim($name)))->map(fn ($part) => mb_substr($part, 0, 1).'***')->implode(' ');
    $worstInvitedRank = $interviews->max('exam_rank') ?? 0;
@endphp

@if ($ranking->isEmpty())
    <p class="text-sm text-slate-500">Belum ada peserta yang lulus pada sesi ini.</p>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                <tr>
                    @if ($selectable) <th class="py-2 pe-3"></th> @endif
                    <th class="py-2 pe-3">#</th>
                    <th class="py-2 pe-3">Peserta</th>
                    <th class="py-2 pe-3">Nilai</th>
                    <th class="py-2 pe-3">Waktu</th>
                    <th class="py-2">Status seleksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($ranking as $attempt)
                    @php
                        $interview = $interviews->get($attempt->user_id);
                        $skipped = ! $interview && $attempt->rank < $worstInvitedRank;
                        $skipReasons = $skipped
                            ? $interviews->filter(fn ($item) => $item->exam_rank > $attempt->rank && $item->skip_reason)->pluck('skip_reason')->unique()
                            : collect();
                        $seconds = $attempt->durationSeconds();
                    @endphp
                    <tr class="{{ $highlight === $attempt->user_id ? 'bg-blue-50' : '' }} {{ $quota && $attempt->rank === $quota ? 'border-b-2 border-blue-300' : '' }}">
                        @if ($selectable)
                            <td class="py-2 pe-3">
                                @unless ($interview)
                                    <input type="checkbox" name="user_ids[]" value="{{ $attempt->user_id }}" class="rounded text-blue-700"
                                        @checked(in_array($attempt->user_id, old('user_ids', $preselect)))>
                                @endunless
                            </td>
                        @endif
                        <td class="py-2 pe-3 font-semibold text-slate-900">{{ $attempt->rank }}</td>
                        <td class="py-2 pe-3">
                            @if ($mask && $highlight !== $attempt->user_id)
                                {{ $maskName($attempt->user->name) }}
                            @elseif ($mask)
                                <strong>Anda</strong>
                            @else
                                <a class="font-medium text-blue-700 hover:underline" href="{{ route('candidates.show', $attempt->user) }}">{{ $attempt->user->name }}</a>
                                <div class="text-xs text-slate-500">{{ $attempt->user->skills->pluck('name')->join(', ') }}</div>
                            @endif
                        </td>
                        <td class="py-2 pe-3">{{ $attempt->score }}</td>
                        <td class="py-2 pe-3 text-slate-500">{{ $seconds === PHP_INT_MAX ? '-' : sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60) }}</td>
                        <td class="py-2">
                            @if ($interview)
                                <x-badge :color="$interview->outcomeColor()">{{ $interview->outcomeLabel() }}</x-badge>
                                @if ($interview->skip_reason && ! $mask)
                                    <div class="mt-1 text-xs text-slate-500">{{ $interview->skip_reason }}</div>
                                @endif
                            @elseif ($skipped)
                                <x-badge color="red">Dilewati</x-badge>
                                @foreach ($skipReasons as $reason)
                                    <div class="mt-1 text-xs text-slate-500">Alasan perusahaan: {{ $reason }}</div>
                                @endforeach
                            @else
                                <x-badge>Cadangan</x-badge>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="mt-3 text-xs text-slate-500">
        Urutan: nilai tertinggi, lalu waktu pengerjaan tercepat, lalu yang lebih dulu mengirim jawaban.
        @if ($quota) Garis biru = batas kuota {{ $quota }} orang. @endif
    </p>
@endif
