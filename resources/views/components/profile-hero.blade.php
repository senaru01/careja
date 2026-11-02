@props(['user', 'subtitle' => null, 'chip' => null])

@php
    $photoUrl = $user->isWorker() ? $user->photoUrl() : null;
@endphp

{{-- Identity card like the photo card on the reference dashboard: the
     candidate's photo fills the card when available, otherwise a gradient. --}}
<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-500 via-blue-700 to-blue-900 p-6 text-white shadow-soft min-h-[17rem] flex flex-col justify-between']) }}>
    @if ($photoUrl)
        <img src="{{ $photoUrl }}" alt="Foto {{ $user->name }}" class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-blue-950/85 via-blue-950/20 to-transparent"></div>
        <x-batik.parang class="absolute inset-x-0 bottom-0 h-2 text-white/40" />
    @else
        <x-batik.kawung class="text-white/[0.08]" size="40" />
        <x-batik.mega-mendung class="absolute -right-12 -top-4 w-56 text-white/20" />
        <x-avatar :user="$user" size="h-16 w-16 text-xl" class="relative !bg-white/20 !text-white ring-4 ring-white/30" />
        <x-batik.parang class="absolute inset-x-0 bottom-0 h-2 text-white/25" />
    @endif

    <div class="relative mt-auto flex items-end justify-between gap-3">
        <div>
            <div class="text-xl font-semibold leading-tight">{{ $user->name }}</div>
            <div class="text-sm text-blue-100">{{ $subtitle ?? $user->role->label() }}</div>
        </div>
        @if ($chip)
            <span class="shrink-0 rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold ring-1 ring-white/40 backdrop-blur">{{ $chip }}</span>
        @endif
    </div>

    {{ $slot }}
</div>
