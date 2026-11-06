<x-app-layout>
    <x-slot name="header">
        <x-welcome-header :user="$user" />
    </x-slot>

    @php
        $steps = [
            ['Lengkapi profil & CV', $profileComplete, route('worker.profile.edit')],
            [$membershipExpired ? 'Perpanjang keanggotaan' : 'Aktifkan akun (QRIS)', $isActiveCandidate, route('worker.payment.create')],
            ['Daftar sesi tes', $user->examAttempts->contains(fn ($attempt) => $attempt->registered_at !== null), route('worker.sessions.index')],
            ['Lulus tes teori LPK', $user->examAttempts->contains('passed', true), null],
            ['Wawancara perusahaan', $user->interviews->contains(fn ($interview) => $interview->isEvaluated()), null],
            ['Medical checkup', $user->interviews->contains(fn ($interview) => $interview->medical_status !== null), null],
        ];
        $doneCount = collect($steps)->where(1, true)->count();
        $progress = (int) round($doneCount / count($steps) * 100);
        $nextStep = collect($steps)->firstWhere(1, false);
    @endphp

    <div class="pt-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <div class="grid lg:grid-cols-3 gap-6">
                <x-profile-hero :user="$user"
                    :subtitle="$user->candidateProfile?->education_level ? $user->candidateProfile->education_level->label().' '.$user->candidateProfile->education_major : 'Pencari Kerja'"
                    :chip="$stage" />

                {{-- Recruitment progress --}}
                <div class="panel p-6 lg:col-span-2 flex flex-col justify-between gap-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="text-lg font-semibold text-slate-900">Progres Rekrutmen</div>
                            <div class="text-sm text-slate-500">Tahap saat ini: {{ $stage }}</div>
                        </div>
                        <div class="text-4xl font-semibold tracking-tight text-slate-900">{{ $progress }}%</div>
                    </div>

                    <div class="flex gap-1.5">
                        @foreach ($steps as [$label, $done])
                            <div class="h-3 flex-1 rounded-full {{ $done ? 'bg-blue-600' : 'bg-slate-200' }}"></div>
                        @endforeach
                    </div>

                    <ul class="grid sm:grid-cols-2 gap-x-8 gap-y-3 text-sm">
                        @foreach ($steps as [$label, $done, $link])
                            <li class="flex items-center justify-between gap-3">
                                <span class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full {{ $done ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                    {{ $label }}
                                </span>
                                @if ($done)
                                    <span class="text-emerald-600 font-medium">Selesai</span>
                                @elseif ($link)
                                    <a href="{{ $link }}" class="font-semibold text-blue-700 hover:underline">Kerjakan</a>
                                @else
                                    <span class="text-slate-400">Menunggu</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    @if ($latestPayment && ! $isActiveCandidate && ! $membershipExpired)
                        <p class="text-xs text-slate-500">
                            Pembayaran terakhir: <strong>{{ $latestPayment->status->label() }}</strong>
                            @if ($latestPayment->note) — {{ $latestPayment->note }} @endif
                        </p>
                    @elseif ($nextStep && $nextStep[2])
                        <a href="{{ $nextStep[2] }}" class="self-start"><x-primary-button type="button">Lanjut: {{ $nextStep[0] }}</x-primary-button></a>
                    @endif
                </div>
            </div>

            @include('worker.partials.membership-status')

            @if ($isActiveCandidate && $openSessions->isNotEmpty())
                <x-card title="Sesi Tes Baru Untuk Anda">
                    <x-slot name="actions">
                        <a class="text-sm font-semibold text-blue-700 hover:underline" href="{{ route('worker.sessions.index') }}">Semua sesi{{ $tokens ? ' · '.$tokens.' token' : '' }}</a>
                    </x-slot>
                    <div class="divide-y divide-slate-100">
                        @foreach ($openSessions as $session)
                            <div class="py-3 {{ $loop->first ? 'pt-0' : '' }} flex flex-wrap items-center justify-between gap-3 text-sm">
                                <div>
                                    <div class="font-semibold text-slate-900">{{ $session->position }} — {{ $session->company?->name }}</div>
                                    <div class="text-slate-500">Butuh {{ $session->quota }} orang · tes {{ $session->starts_at->translatedFormat('d M Y H:i') }} WIB · {{ $session->organization->name }}</div>
                                </div>
                                <a href="{{ route('worker.sessions.show', $session) }}"><x-secondary-button>Lihat Kualifikasi</x-secondary-button></a>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif

            <div class="grid lg:grid-cols-2 gap-6">
                <x-card title="Sesi Tes Saya">
                    @forelse ($user->examAttempts->filter(fn ($attempt) => $attempt->registered_at !== null) as $attempt)
                        <div class="py-4 {{ $loop->first ? 'pt-0' : 'border-t border-slate-100' }}">
                            <div class="flex flex-wrap justify-between gap-3">
                                <x-attempt-result :attempt="$attempt" />
                                @if (! $attempt->isSubmitted() && $attempt->isRegistered() && $attempt->exam->isRunning())
                                    <a href="{{ route('worker.attempts.show', $attempt) }}">
                                        <x-primary-button type="button">{{ $attempt->isStarted() ? 'Lanjutkan Tes' : 'Kerjakan Tes' }}</x-primary-button>
                                    </a>
                                @else
                                    <a class="text-sm font-semibold text-blue-700 hover:underline" href="{{ route('worker.sessions.show', $attempt->exam) }}">Detail sesi</a>
                                @endif
                            </div>
                            <div class="text-xs text-slate-500 mt-1">{{ $attempt->exam->position }} — {{ $attempt->exam->company?->name }} · tes {{ $attempt->exam->starts_at?->translatedFormat('d M Y H:i') }} WIB · {{ $attempt->exam->organization->name }}</div>

                            @if ($attempt->evaluation)
                                <div class="mt-3 rounded-2xl bg-blue-50 p-4 text-sm">
                                    <div class="font-semibold text-blue-900">Evaluasi dari LPK (bahan skill up)</div>
                                    <p class="mt-1 text-blue-900 whitespace-pre-line">{{ $attempt->evaluation }}</p>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">
                            {{ $isActiveCandidate ? 'Anda belum mendaftar sesi tes. Pilih sesi yang sesuai keahlian Anda di menu Sesi Tes.' : 'Sesi tes bisa diikuti setelah keanggotaan aktif.' }}
                        </p>
                    @endforelse
                </x-card>

                <x-card title="Wawancara">
                    @forelse ($user->interviews as $interview)
                        <div class="py-4 {{ $loop->first ? 'pt-0' : 'border-t border-slate-100' }} text-sm space-y-1">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="font-semibold text-slate-900">{{ $interview->organization->name }}</span>
                                <x-badge :color="$interview->outcomeColor()">{{ $interview->outcomeLabel() }}</x-badge>
                            </div>
                            <div class="text-slate-600">{{ $interview->scheduled_at->format('d M Y H:i') }} · {{ $interview->location }}</div>
                            @if ($interview->notes)
                                <div class="text-slate-500">Catatan: {{ $interview->notes }}</div>
                            @endif
                            @if ($interview->isEvaluated())
                                <div class="mt-2 rounded-2xl bg-blue-50 p-4">
                                    <div class="font-semibold text-blue-900">Nilai wawancara: {{ $interview->score }} / 100</div>
                                    <p class="mt-1 text-blue-900 whitespace-pre-line">{{ $interview->evaluation }}</p>
                                </div>
                            @endif
                            @if ($interview->needsMedicalCheck())
                                @if ($pendingMedical = $interview->pendingMedicalPayment())
                                    <div class="mt-2">
                                        @include('payments.partials.qris-waiter', ['payment' => $pendingMedical, 'caption' => 'Medical checkup · '.$interview->organization->name])
                                    </div>
                                @elseif ($interview->hasPaidMedicalCheckup())
                                    <div class="mt-2 rounded-2xl bg-emerald-50 p-4 text-emerald-900">Biaya medical checkup sudah dibayar. Menunggu hasil pemeriksaan dari perusahaan.</div>
                                @else
                                    <div class="mt-2 rounded-2xl bg-amber-50 p-4 text-amber-900 ring-1 ring-amber-200">
                                        <div class="font-semibold">Selamat, Anda lolos wawancara!</div>
                                        <p class="mt-1">Tahap akhir adalah medical checkup. Bayar biaya medical checkup Rp {{ number_format(\App\Enums\PaymentPurpose::MedicalCheckup->fee($interview->exam?->organization), 0, ',', '.') }} agar pemeriksaan dapat dijadwalkan.</p>
                                        @if ($qrisAvailable)
                                            <form method="POST" action="{{ route('worker.medical-payment.store', $interview) }}" class="mt-3">
                                                @csrf
                                                <x-primary-button>Bayar Medical Checkup via QRIS</x-primary-button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            @endif
                            @if ($interview->medical_status)
                                <div class="mt-2 rounded-2xl p-4 {{ $interview->medical_status->value === 'fit' ? 'bg-emerald-50 text-emerald-900' : 'bg-red-50 text-red-900' }}">
                                    <div class="font-semibold">Medical checkup: {{ $interview->medical_status->label() }}</div>
                                    <p class="mt-1 whitespace-pre-line">{{ $interview->medical_notes }}</p>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Perusahaan memilih peserta lulus dari peringkat sesi tes untuk diundang wawancara.</p>
                    @endforelse
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
