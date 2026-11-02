@props(['size' => 48, 'rotate' => 45])

{{-- Kawung batik motif (four palm-fruit ovals around a centre), tiled to fill its container.
     Colour comes from `currentColor`, so set it with a text-* class, e.g. text-blue-700/10. --}}
@php($id = 'kawung-'.\Illuminate\Support\Str::random(8))

<svg {{ $attributes->merge(['class' => 'pointer-events-none absolute inset-0 h-full w-full']) }} aria-hidden="true">
    <defs>
        <pattern id="{{ $id }}" width="{{ $size }}" height="{{ $size }}" patternUnits="userSpaceOnUse" patternTransform="rotate({{ $rotate }}) scale({{ $size / 48 }})">
            <g fill="none" stroke="currentColor" stroke-width="1.3">
                <ellipse cx="24" cy="11" rx="6.5" ry="10" />
                <ellipse cx="24" cy="37" rx="6.5" ry="10" />
                <ellipse cx="11" cy="24" rx="10" ry="6.5" />
                <ellipse cx="37" cy="24" rx="10" ry="6.5" />
                <ellipse cx="24" cy="11" rx="2" ry="4.5" />
                <ellipse cx="24" cy="37" rx="2" ry="4.5" />
                <ellipse cx="11" cy="24" rx="4.5" ry="2" />
                <ellipse cx="37" cy="24" rx="4.5" ry="2" />
            </g>
            <g fill="currentColor">
                <circle cx="24" cy="24" r="2.4" />
                <circle cx="0" cy="0" r="2" />
                <circle cx="48" cy="0" r="2" />
                <circle cx="0" cy="48" r="2" />
                <circle cx="48" cy="48" r="2" />
            </g>
        </pattern>
    </defs>
    <rect width="100%" height="100%" fill="url(#{{ $id }})" />
</svg>
