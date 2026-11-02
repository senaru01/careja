{{-- Parang batik motif: diagonal S-curve bands with small "mlinjon" dots, used as a
     decorative strip. Give it a height (h-2, h-6...) and a colour (text-blue-700/30...). --}}
@php($id = 'parang-'.\Illuminate\Support\Str::random(8))

<svg {{ $attributes->merge(['class' => 'pointer-events-none block w-full']) }} aria-hidden="true" preserveAspectRatio="none">
    <defs>
        <pattern id="{{ $id }}" width="22" height="22" patternUnits="userSpaceOnUse" patternTransform="rotate(-45)">
            <path d="M0 11 C5.5 2, 16.5 20, 22 11" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
            <path d="M0 0 C5.5 -9, 16.5 9, 22 0 M0 22 C5.5 13, 16.5 31, 22 22" fill="none" stroke="currentColor" stroke-width="1.2" opacity="0.6" />
            <circle cx="11" cy="4" r="1.4" fill="currentColor" />
            <circle cx="11" cy="18" r="1.4" fill="currentColor" />
        </pattern>
    </defs>
    <rect width="100%" height="100%" fill="url(#{{ $id }})" />
</svg>
