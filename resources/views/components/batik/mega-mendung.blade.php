{{-- Mega Mendung batik motif: a cloud outline repeated in concentric, fading layers.
     Position and size it with classes (absolute -right-10 top-0 w-72 ...) and colour with text-*. --}}
<svg {{ $attributes->merge(['class' => 'pointer-events-none']) }} viewBox="0 0 220 130" fill="none" aria-hidden="true">
    @foreach ([1, 0.82, 0.64, 0.46, 0.28] as $index => $scale)
        <path transform="translate(110 72) scale({{ $scale }}) translate(-110 -72)"
              d="M22 104 C4 104 2 74 26 70 C22 44 54 34 68 52 C74 22 116 16 128 44 C144 24 184 32 180 62 C208 62 214 104 186 104 Z"
              stroke="currentColor" stroke-width="{{ 3.2 - $index * 0.4 }}" stroke-linejoin="round"
              opacity="{{ 1 - $index * 0.15 }}" />
    @endforeach
    <path d="M10 118 C60 108 160 108 210 118" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity="0.5" />
</svg>
