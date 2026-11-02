{{-- Everything announced for a test session, so participants know exactly what they apply for. --}}
@props(['exam'])

@php($phase = $exam->phase())

<div {{ $attributes->merge(['class' => 'panel relative overflow-hidden p-6']) }}>
    <x-batik.kawung class="text-blue-600/[0.04]" size="36" />

    <div class="relative space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-blue-700">{{ $exam->company?->name ?? 'Perusahaan belum dipilih' }}</div>
                <div class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ $exam->position ?? $exam->title }}</div>
                <div class="text-sm text-slate-500">{{ $exam->title }} · diselenggarakan {{ $exam->organization->name }}</div>
            </div>
            <x-badge :color="$phase->color()">{{ $phase->label() }}</x-badge>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
            <div class="rounded-2xl bg-slate-50 p-3">
                <div class="text-xs text-slate-500">Dibutuhkan</div>
                <div class="text-xl font-semibold text-slate-900">{{ $exam->quota ?? '-' }} <span class="text-sm font-normal text-slate-500">orang</span></div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-3">
                <div class="text-xs text-slate-500">Peserta siap tes</div>
                <div class="text-xl font-semibold text-slate-900">{{ $exam->participants_count ?? $exam->participants()->count() }}
                    @if ($exam->min_participants) <span class="text-sm font-normal text-slate-500">/ target {{ $exam->min_participants }}</span> @endif
                </div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-3">
                <div class="text-xs text-slate-500">Durasi tes</div>
                <div class="text-xl font-semibold text-slate-900">{{ $exam->duration_minutes }} <span class="text-sm font-normal text-slate-500">menit</span></div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-3">
                <div class="text-xs text-slate-500">Nilai lulus</div>
                <div class="text-xl font-semibold text-slate-900">{{ $exam->passing_score }}</div>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4 text-sm">
            <div>
                <div class="font-semibold text-slate-900">Kualifikasi</div>
                <p class="mt-1 text-slate-700 whitespace-pre-line">{{ $exam->qualifications ?? '-' }}</p>
            </div>
            <div>
                <div class="font-semibold text-slate-900">Jadwal tes</div>
                @if ($exam->starts_at)
                    <p class="mt-1 text-slate-700">{{ $exam->starts_at->translatedFormat('l, d M Y H:i') }} – {{ $exam->ends_at?->translatedFormat('d M Y H:i') }} WIB</p>
                    <p class="text-xs text-slate-500">Tes bisa dimulai kapan saja dalam rentang ini; waktu {{ $exam->duration_minutes }} menit berjalan sejak tombol Mulai ditekan.</p>
                @else
                    <p class="mt-1 text-slate-500">Belum dijadwalkan</p>
                @endif
                @if ($exam->relationLoaded('reschedules') && $exam->reschedules->isNotEmpty())
                    <div class="mt-3 rounded-2xl bg-amber-50 p-3 text-amber-900 ring-1 ring-amber-200">
                        <div class="font-semibold">Jadwal pernah dimundurkan {{ $exam->reschedules->count() }}×</div>
                        <ul class="mt-1 space-y-1 text-xs">
                            @foreach ($exam->reschedules as $reschedule)
                                <li>{{ $reschedule->previous_starts_at->format('d M Y H:i') }} → {{ $reschedule->starts_at->format('d M Y H:i') }} · {{ $reschedule->reason }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        @if ($exam->description)
            <p class="text-sm text-slate-600 whitespace-pre-line">{{ $exam->description }}</p>
        @endif
    </div>
</div>
