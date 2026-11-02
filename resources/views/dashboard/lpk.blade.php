<x-app-layout>
    <x-slot name="header">
        <x-welcome-header :user="auth()->user()" />
    </x-slot>

    <div class="pt-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <div class="grid lg:grid-cols-3 gap-6">
                <x-profile-hero :user="auth()->user()" :subtitle="$organization->name" chip="LPK" />

                <div class="lg:col-span-2 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        @foreach ($stats as $label => $value)
                            <div class="panel p-5">
                                <div class="text-sm text-slate-500">{{ $label }}</div>
                                <div class="mt-1 text-4xl font-semibold tracking-tight text-slate-900">{{ $value }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('lpk.exams.create') }}"><x-primary-button type="button">Buat Sesi Tes</x-primary-button></a>
                        <a href="{{ route('lpk.exams.index') }}"><x-secondary-button>Kelola Sesi Tes</x-secondary-button></a>
                    </div>
                </div>
            </div>

            <x-card title="Sesi Tes Berjalan">
                @forelse ($upcomingSessions as $session)
                    @php($phase = $session->phase())
                    <div class="py-3 {{ $loop->first ? 'pt-0' : 'border-t border-slate-100' }} flex flex-wrap items-center justify-between gap-3 text-sm">
                        <div>
                            <a class="font-semibold text-blue-700 hover:underline" href="{{ route('lpk.exams.show', $session) }}">{{ $session->title }}</a>
                            <div class="text-slate-500">{{ $session->position }} — {{ $session->company?->name }} · {{ $session->starts_at->format('d M Y H:i') }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-badge :color="$phase->color()">{{ $phase->label() }}</x-badge>
                            <x-badge :color="$session->min_participants && $session->participants_count < $session->min_participants ? 'red' : 'blue'">
                                {{ $session->participants_count }}{{ $session->min_participants ? ' / '.$session->min_participants : '' }} siap tes
                            </x-badge>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada sesi tes yang terbit.</p>
                @endforelse
            </x-card>

            <div class="grid lg:grid-cols-2 gap-6">
                <x-card title="Menunggu Evaluasi Anda">
                    @include('dashboard.partials.attempt-table', ['attempts' => $needsEvaluation, 'empty' => 'Semua hasil tes sudah dievaluasi.'])
                </x-card>

                <x-card title="Hasil Tes Terbaru">
                    @include('dashboard.partials.attempt-table', ['attempts' => $recentAttempts, 'empty' => 'Belum ada tes yang dikerjakan.'])
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
