<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Sesi Tes</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            @if ($tokens > 0)
                <div class="panel p-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="text-lg font-semibold text-slate-900">Anda punya {{ $tokens }} token tes</div>
                        <p class="text-sm text-slate-500">Satu token = ikut satu sesi tes tanpa bayar lagi.</p>
                    </div>
                    <x-badge color="indigo">{{ $tokens }} token</x-badge>
                </div>
            @endif

            @unless ($isActiveMember)
                <div class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">
                    Aktifkan <a class="font-semibold underline" href="{{ route('worker.payment.create') }}">keanggotaan</a> terlebih dahulu untuk bisa mendaftar sesi tes.
                </div>
            @endunless

            <div>
                <h3 class="mb-3 text-lg font-semibold text-slate-900">Pendaftaran Dibuka</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    @forelse ($openSessions as $exam)
                        <a href="{{ route('worker.sessions.show', $exam) }}" class="panel block p-5 transition hover:ring-2 hover:ring-blue-300">
                            <div class="text-xs font-semibold uppercase tracking-wide text-blue-700">{{ $exam->company?->name }}</div>
                            <div class="mt-1 text-lg font-semibold text-slate-900">{{ $exam->position }}</div>
                            <div class="text-sm text-slate-500">{{ $exam->title }} · {{ $exam->organization->name }}</div>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <x-badge color="blue">Butuh {{ $exam->quota }} orang</x-badge>
                                <x-badge>{{ $exam->participants_count }} peserta siap tes</x-badge>
                                <x-badge>{{ $exam->starts_at->translatedFormat('d M Y H:i') }} WIB</x-badge>
                            </div>
                            <p class="mt-3 line-clamp-2 text-sm text-slate-600">{{ $exam->qualifications }}</p>
                            <div class="mt-3 text-sm font-semibold text-blue-700">Rp {{ number_format($fee, 0, ',', '.') }} · Lihat detail →</div>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada sesi tes yang membuka pendaftaran. Anda akan mendapat email saat sesi baru terbit.</p>
                    @endforelse
                </div>
            </div>

            <x-card title="Sesi Saya">
                @forelse ($myAttempts as $attempt)
                    <div class="py-3 {{ $loop->first ? 'pt-0' : 'border-t border-slate-100' }} flex flex-wrap items-center justify-between gap-3 text-sm">
                        <div>
                            <div class="font-semibold text-slate-900">{{ $attempt->exam->position ?? $attempt->exam->title }} — {{ $attempt->exam->company?->name }}</div>
                            <div class="text-slate-500">{{ $attempt->exam->starts_at?->translatedFormat('d M Y H:i') }} WIB · {{ $attempt->exam->organization->name }}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-badge :color="match (true) { $attempt->passed === true => 'green', $attempt->passed === false => 'red', $attempt->isWithdrawn() || $attempt->isAbsent() => 'gray', default => 'yellow' }">{{ $attempt->statusLabel() }}</x-badge>
                            <a class="font-semibold text-blue-700 hover:underline" href="{{ route('worker.sessions.show', $attempt->exam) }}">Buka</a>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Anda belum mendaftar sesi tes.</p>
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
