@props(['attempt'])

{{-- Score summary of one theory exam attempt, shown identically to candidate, LPK and company. --}}
<div class="flex flex-wrap items-center gap-3">
    <span class="font-medium text-gray-900">{{ $attempt->exam->title }}</span>
    <x-badge :color="match (true) { $attempt->passed === true => 'green', $attempt->passed === false => 'red', default => 'yellow' }">
        {{ $attempt->statusLabel() }}
    </x-badge>
    @if ($attempt->isSubmitted())
        <span class="text-sm text-gray-600">
            Nilai <strong>{{ $attempt->score }}</strong> / 100
            ({{ $attempt->correct_count }} dari {{ $attempt->total_questions }} benar, batas lulus {{ $attempt->exam->passing_score }})
        </span>
        <span class="text-xs text-gray-400">{{ $attempt->submitted_at->format('d M Y H:i') }}</span>
    @endif
</div>
