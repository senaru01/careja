@props(['user', 'size' => 'h-10 w-10 text-xs'])

@php
    $photoUrl = $user->isWorker() ? $user->photoUrl() : null;
    $initials = collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->join('');
@endphp

@if ($photoUrl)
    <img src="{{ $photoUrl }}" alt="Foto {{ $user->name }}" {{ $attributes->merge(['class' => "$size shrink-0 rounded-full object-cover"]) }}>
@else
    <span {{ $attributes->merge(['class' => "$size shrink-0 rounded-full bg-blue-100 text-blue-800 font-semibold inline-flex items-center justify-center"]) }}>
        {{ $initials }}
    </span>
@endif
