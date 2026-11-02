{{-- Silhouettes of job seekers walking toward a job board, in front of a faint city skyline.
     Uses currentColor; set colour with a text-* class on the component. --}}
<svg {{ $attributes->merge(['class' => 'w-full h-auto']) }} viewBox="0 0 640 300" fill="currentColor" role="img" aria-label="Siluet para pencari kerja menuju papan lowongan">
    {{-- City skyline --}}
    <g opacity="0.14">
        <rect x="20" y="120" width="46" height="150" rx="3" />
        <rect x="72" y="80" width="38" height="190" rx="3" />
        <rect x="116" y="140" width="52" height="130" rx="3" />
        <rect x="380" y="100" width="44" height="170" rx="3" />
        <rect x="430" y="60" width="36" height="210" rx="3" />
        <rect x="472" y="130" width="58" height="140" rx="3" />
        <rect x="536" y="90" width="40" height="180" rx="3" />
        <rect x="582" y="150" width="40" height="120" rx="3" />
    </g>

    {{-- Ground --}}
    <rect x="0" y="268" width="640" height="4" rx="2" opacity="0.35" />

    {{-- 1. Man with briefcase, walking --}}
    <g opacity="0.95">
        <circle cx="96" cy="128" r="15" />
        <path d="M80 150 Q96 142 112 150 L116 214 L76 214 Z" />
        <path d="M80 212 L70 268 L84 268 L96 220 Z" />
        <path d="M100 214 L118 266 L132 262 L112 212 Z" />
        <path d="M112 154 L126 204 L118 207 L104 160 Z" />
        <rect x="114" y="204" width="28" height="22" rx="4" />
        <path d="M122 204 v-5 h12 v5" fill="none" stroke="currentColor" stroke-width="3" />
        <path d="M80 154 L70 196 L78 198 L88 160 Z" />
    </g>

    {{-- 2. Woman in hijab holding a folder --}}
    <g opacity="0.8">
        <path d="M200 112 C186 112 180 124 180 134 C180 148 188 156 200 158 C212 156 220 148 220 134 C220 124 214 112 200 112 Z" />
        <path d="M178 140 Q200 170 222 140 L230 176 Q200 186 170 176 Z" />
        <path d="M176 170 L224 170 L238 268 L162 268 Z" />
        <rect x="214" y="186" width="24" height="30" rx="3" transform="rotate(-8 226 201)" />
    </g>

    {{-- 3. Young man checking a phone --}}
    <g opacity="0.65">
        <circle cx="296" cy="132" r="14" />
        <path d="M280 152 Q296 145 312 152 L314 214 L278 214 Z" />
        <path d="M282 212 L280 268 L293 268 L296 218 Z" />
        <path d="M298 212 L304 268 L317 268 L312 212 Z" />
        <path d="M310 156 L322 182 L318 186 L304 162 Z" />
        <path d="M322 182 L312 150 L318 148 L328 178 Z" />
        <rect x="306" y="138" width="11" height="18" rx="2" />
    </g>

    {{-- 4. Person pointing at the job board --}}
    <g opacity="0.9">
        <circle cx="404" cy="126" r="15" />
        <path d="M388 148 Q404 140 420 148 L422 214 L386 214 Z" />
        <path d="M390 212 L384 268 L398 268 L404 218 Z" />
        <path d="M406 212 L414 268 L428 268 L420 212 Z" />
        <path d="M418 152 L462 136 L464 144 L420 162 Z" />
        <path d="M388 154 L378 196 L386 198 L396 160 Z" />
    </g>

    {{-- Job board --}}
    <g>
        <rect x="508" y="150" width="8" height="118" rx="2" />
        <rect x="560" y="150" width="8" height="118" rx="2" />
        <rect x="470" y="78" width="136" height="88" rx="10" />
        <g fill="none" stroke="#1e3a8a" stroke-linecap="round" opacity="0.9">
            <path d="M486 98 h70" stroke-width="7" />
            <path d="M486 120 h104" stroke-width="4" />
            <path d="M486 134 h88" stroke-width="4" />
            <path d="M486 148 h96" stroke-width="4" />
        </g>
        <circle cx="586" cy="98" r="7" fill="#1e3a8a" opacity="0.9" />
    </g>
</svg>
