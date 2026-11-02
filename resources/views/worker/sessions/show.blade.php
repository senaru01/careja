<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Sesi Tes</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <x-session-summary :exam="$exam" />

            @if ($pendingPayment)
                @include('payments.partials.qris-waiter', ['payment' => $pendingPayment, 'caption' => 'Pendaftaran tes · terdaftar otomatis setelah dibayar'])

            @elseif ($attempt?->isRegistered())
                <x-card title="Status Anda">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="space-y-1 text-sm">
                            <x-badge :color="match (true) { $attempt->passed === true => 'green', $attempt->passed === false => 'red', $attempt->isAbsent() => 'gray', default => 'blue' }">{{ $attempt->statusLabel() }}</x-badge>
                            <p class="text-slate-600">Terdaftar sejak {{ $attempt->registered_at->translatedFormat('d M Y H:i') }}.</p>
                            @if ($exam->isOpenForRegistration())
                                <p class="text-slate-600">Tes dibuka {{ $exam->starts_at->diffForHumans() }}.</p>
                            @endif
                        </div>
                        @if ($exam->isRunning() || $attempt->isSubmitted())
                            <a href="{{ route('worker.attempts.show', $attempt) }}">
                                <x-primary-button type="button">{{ $attempt->isSubmitted() ? 'Lihat Hasil' : ($attempt->isStarted() ? 'Lanjutkan Tes' : 'Kerjakan Tes') }}</x-primary-button>
                            </a>
                        @endif
                    </div>

                    @if ($attempt->canWithdraw())
                        <div class="mt-5 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
                            <div class="font-semibold">Jadwal dimundurkan setelah Anda mendaftar</div>
                            <p class="mt-1">Jika tidak bisa ikut di jadwal baru, keluar dari sesi sekarang dan dapatkan <strong>1 token</strong> untuk ikut sesi tes lain tanpa bayar lagi.
                                Jika Anda tidak keluar dan tidak hadir, biaya tes hangus.</p>
                            <form method="POST" action="{{ route('worker.sessions.withdraw', $exam) }}" class="mt-3"
                                  onsubmit="return confirm('Keluar dari sesi ini? Anda mendapat 1 token dan tidak bisa mendaftar ulang di sesi ini.')">
                                @csrf
                                <x-danger-button>Keluar Sesi & Ambil Token</x-danger-button>
                            </form>
                        </div>
                    @endif
                </x-card>

            @elseif ($attempt?->isWithdrawn())
                <x-card title="Status Anda">
                    <p class="text-sm text-slate-600">Anda keluar dari sesi ini pada {{ $attempt->withdrawn_at->translatedFormat('d M Y H:i') }} dan sudah mendapat 1 token.</p>
                </x-card>

            @elseif ($registerCheck->allowed())
                <x-card title="Daftar Sesi Tes">
                    <form method="POST" action="{{ route('worker.sessions.register', $exam) }}" class="space-y-4" x-data="{ confirmed: false }">
                        @csrf
                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4 text-sm">
                            <input type="checkbox" name="qualification_confirmed" value="1" class="mt-0.5 rounded text-blue-700" x-model="confirmed" @checked(old('qualification_confirmed'))>
                            <span>
                                Saya sudah membaca dan <strong>memenuhi kualifikasi</strong> sesi ini (posisi <strong>{{ $exam->position }}</strong>).
                                Saya mengerti bahwa perusahaan hanya mengambil peserta lulus yang sesuai kualifikasi, dan biaya tes tidak dapat dikembalikan
                                kecuali jadwal dimundurkan (diganti 1 token).
                            </span>
                        </label>
                        <x-input-error :messages="$errors->get('qualification_confirmed')" />

                        <div class="flex flex-wrap gap-3">
                            @if ($qrisAvailable)
                                <button type="submit" name="pay_with" value="qris" :disabled="! confirmed"
                                    class="rounded-full bg-blue-700 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:opacity-40">
                                    Bayar Rp {{ number_format($fee, 0, ',', '.') }} (QRIS)
                                </button>
                            @endif
                            @if ($tokens > 0)
                                <button type="submit" name="pay_with" value="token" :disabled="! confirmed"
                                    class="rounded-full bg-white px-6 py-3 text-sm font-semibold text-blue-900 ring-1 ring-blue-200 transition hover:bg-blue-50 disabled:opacity-40">
                                    Pakai 1 Token (sisa {{ $tokens }})
                                </button>
                            @endif
                        </div>
                        <x-input-error :messages="$errors->get('pay_with')" />
                    </form>
                </x-card>

            @elseif (! $exam->hasEnded())
                <div class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">{{ $registerCheck->message() }}</div>
            @endif

            @if ($exam->hasEnded())
                <x-card title="Peringkat Final">
                    @php($myRank = $ranking->firstWhere('user_id', auth()->id())?->rank)
                    @if ($myRank)
                        <p class="mb-4 text-sm text-slate-700">Peringkat Anda: <strong>#{{ $myRank }}</strong> dari {{ $ranking->count() }} peserta lulus (kuota {{ $exam->quota }} orang).</p>
                    @endif
                    <x-session-ranking :ranking="$ranking" :interviews="$interviews" :quota="$exam->quota" mask :highlight="auth()->id()" />
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>
