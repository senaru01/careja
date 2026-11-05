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
                        <a href="{{ route('lpk.recruitments.index') }}"><x-secondary-button>Permintaan PT</x-secondary-button></a>
                    </div>
                </div>
            </div>

            @if ($openRequests->isNotEmpty())
                <x-card title="Permintaan Rekrutmen Baru dari Perusahaan">
                    <x-slot name="actions">
                        <a class="text-sm font-semibold text-blue-700 hover:underline" href="{{ route('lpk.recruitments.index') }}">Semua permintaan</a>
                    </x-slot>
                    @foreach ($openRequests as $recruitment)
                        <div class="py-3 {{ $loop->first ? 'pt-0' : 'border-t border-slate-100' }} flex flex-wrap items-center justify-between gap-3 text-sm">
                            <div>
                                <div class="font-semibold text-slate-900">{{ $recruitment->company->name }} · {{ $recruitment->title }}</div>
                                <div class="text-slate-500">
                                    {{ $recruitment->positions->map(fn ($position) => $position->position.' '.$position->quota.' org')->join(', ') }}
                                    @if ($recruitment->needed_by) · target {{ $recruitment->needed_by->translatedFormat('d M Y') }} @endif
                                </div>
                            </div>
                            <a href="{{ route('lpk.exams.create', ['permintaan' => $recruitment->id]) }}"><x-primary-button type="button">Buat Sesi Tes</x-primary-button></a>
                        </div>
                    @endforeach
                </x-card>
            @endif

            <x-card title="Sesi Tes Berjalan">
                @forelse ($upcomingSessions as $session)
                    @php($phase = $session->phase())
                    <div class="py-3 {{ $loop->first ? 'pt-0' : 'border-t border-slate-100' }} flex flex-wrap items-center justify-between gap-3 text-sm">
                        <div>
                            <a class="font-semibold text-blue-700 hover:underline" href="{{ route('lpk.exams.show', $session) }}">{{ $session->title }}</a>
                            <div class="text-slate-500">{{ $session->position }} — {{ $session->company?->name }} · {{ $session->starts_at->format('d M Y H:i') }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($session->isWaitingForStart() || $session->isRunning())
                                <a class="text-sm font-semibold text-amber-700 hover:underline" href="{{ route('sessions.live', $session) }}">{{ $session->isRunning() ? 'Live Score' : 'Mulai Tes' }}</a>
                            @endif
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

            <x-card title="Kandidat Aktif">
                <x-slot name="actions">
                    <a class="text-sm font-semibold text-blue-700 hover:underline" href="{{ route('candidates.index') }}">Semua kandidat ({{ $stats['Kandidat Aktif'] }})</a>
                </x-slot>
                <p class="mb-4 text-sm text-slate-500">Semua pencari kerja dengan keanggotaan aktif, termasuk yang belum ikut sesi tes. Hubungi lewat WhatsApp jika ada yang perlu dikonfirmasi.</p>
                @if ($activeCandidates->isEmpty())
                    <p class="text-sm text-slate-500">Belum ada kandidat aktif.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                                <tr>
                                    <th class="py-2 pe-3">Kandidat</th>
                                    <th class="py-2 pe-3">Umur</th>
                                    <th class="py-2 pe-3">Pendidikan</th>
                                    <th class="py-2 pe-3">Pengalaman</th>
                                    <th class="py-2 pe-3">Domisili</th>
                                    <th class="py-2 pe-3">Kontak</th>
                                    <th class="py-2">Aktif s/d</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($activeCandidates as $candidate)
                                    @php($profile = $candidate->candidateProfile)
                                    <tr class="align-top">
                                        <td class="py-2 pe-3">
                                            <div class="flex items-center gap-2">
                                                <x-avatar :user="$candidate" />
                                                <a class="font-medium text-blue-700 hover:underline" href="{{ route('candidates.show', $candidate) }}">{{ $candidate->name }}</a>
                                            </div>
                                        </td>
                                        <td class="py-2 pe-3 whitespace-nowrap">{{ $profile?->birth_date ? $profile->birth_date->age.' th' : '-' }}</td>
                                        <td class="py-2 pe-3">{{ $profile?->education_level?->label() }} {{ $profile?->education_major }}</td>
                                        <td class="py-2 pe-3">
                                            <div class="whitespace-nowrap">{{ ucfirst($candidate->workExperienceLabel()) }}</div>
                                            <div class="text-xs text-slate-500">{{ implode(', ', array_slice($candidate->workFields(), 0, 3)) }}</div>
                                        </td>
                                        <td class="py-2 pe-3">{{ $profile?->city }}</td>
                                        <td class="py-2 pe-3"><x-candidate-contact :candidate="$candidate" /></td>
                                        <td class="py-2 whitespace-nowrap text-slate-500">{{ $candidate->payments->max('expires_at')?->translatedFormat('d M Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
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
