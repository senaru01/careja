@props(['active'])

@php
$classes = ($active ?? false)
    ? 'inline-flex items-center border-b-2 border-blue-900 pb-3 text-sm font-semibold text-blue-900'
    : 'inline-flex items-center border-b-2 border-transparent pb-3 text-sm font-medium text-slate-600 transition hover:text-blue-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
