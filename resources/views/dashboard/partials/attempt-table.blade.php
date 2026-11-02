@if ($attempts->isEmpty())
    <p class="text-sm text-slate-500">{{ $empty }}</p>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="py-2 pe-4">Kandidat</th>
                    <th class="py-2 pe-4">Ujian</th>
                    <th class="py-2 pe-4">Nilai</th>
                    <th class="py-2 pe-4">Status</th>
                    <th class="py-2">Dikirim</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($attempts as $attempt)
                    <tr>
                        <td class="py-2 pe-4"><a class="text-blue-700 underline" href="{{ route('candidates.show', $attempt->user) }}">{{ $attempt->user->name }}</a></td>
                        <td class="py-2 pe-4">{{ $attempt->exam->title }}</td>
                        <td class="py-2 pe-4">{{ $attempt->score ?? '-' }}</td>
                        <td class="py-2 pe-4">
                            <x-badge :color="$attempt->passed ? 'green' : 'red'">{{ $attempt->statusLabel() }}</x-badge>
                        </td>
                        <td class="py-2 text-slate-500">{{ $attempt->submitted_at?->format('d M Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
