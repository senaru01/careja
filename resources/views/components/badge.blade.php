@props(['color' => 'gray'])

@php
$colors = [
    'gray' => 'bg-slate-100 text-slate-600',
    'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    'red' => 'bg-red-50 text-red-600 ring-red-200',
    'yellow' => 'bg-amber-50 text-amber-700 ring-amber-200',
    'blue' => 'bg-blue-50 text-blue-700 ring-blue-200',
    'indigo' => 'bg-blue-900 text-white ring-blue-900',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ring-transparent '.($colors[$color] ?? $colors['gray'])]) }}>
    {{ $slot }}
</span>
