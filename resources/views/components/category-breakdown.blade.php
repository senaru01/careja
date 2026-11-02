@props(['attempt'])

{{-- Score per question category (e.g. Psikotes vs Pengetahuan Umum), shown when the exam mixes categories. --}}
@php($breakdown = $attempt->isSubmitted() ? $attempt->categoryBreakdown() : collect())

@if ($breakdown->count() > 1)
    <div {{ $attributes->merge(['class' => 'grid sm:grid-cols-2 gap-3']) }}>
        @foreach ($breakdown as $name => $result)
            @php($percent = $result['total'] > 0 ? (int) round($result['correct'] / $result['total'] * 100) : 0)
            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                <div class="flex justify-between text-sm">
                    <span class="font-medium text-slate-700">{{ $name }}</span>
                    <span class="font-semibold text-slate-900">{{ $percent }}</span>
                </div>
                <div class="mt-2 h-2 rounded-full bg-slate-200 overflow-hidden">
                    <div class="h-full rounded-full {{ $percent >= $attempt->exam->passing_score ? 'bg-blue-600' : 'bg-red-400' }}" style="width: {{ $percent }}%"></div>
                </div>
                <div class="mt-1 text-xs text-slate-500">{{ $result['correct'] }} dari {{ $result['total'] }} benar</div>
            </div>
        @endforeach
    </div>
@endif
