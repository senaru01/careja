@props(['active'])

@php
$classes = ($active ?? false)
    ? 'block w-full rounded-xl bg-blue-900 px-4 py-2 text-start text-sm font-semibold text-white'
    : 'block w-full rounded-xl px-4 py-2 text-start text-sm font-medium text-slate-600 hover:bg-white/70';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
