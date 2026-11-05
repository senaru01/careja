<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Sesi Tes</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <x-session-summary :exam="$exam" />

            @if ($pendingPayment?->isMidtrans())
                @include('payments.partials.qris-waiter', ['payment' => $pendingPayment, 'caption' => 'Pendaftaran tes · terdaftar otomatis setelah dibayar'])

            @elseif ($pendingPayment)
                @include('payments.partials.manual-waiter', ['payment' => $pendingPayment, 'caption' => 'Pendaftaran tes · terdaftar otomatis setelah diverifikasi'])

            @elseif ($attempt?->isRegistered())
                <x-card title="Status Anda">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="space-y-1 text-sm">
                            <x-badge :color="match (true) { $attempt->passed === true => 'green', $attempt->passed === false => 'red', $attempt->isAbsent() => 'gray', default => 'blue' }">{{ $attempt->statusLabel() }}</x-badge>
                            <p class="text-slate-600">Terdaftar sejak {{ $attempt->registered_at->translatedFormat('d M Y H:i') }}@if ($attempt->position) untuk posisi <strong>{{ $attempt->position->position }}</strong>@endif.</p>
                            @if ($exam->isOpenForRegistration())
                                <p class="text-slate-600">Tes dijadwalkan {{ $exam->starts_at->diffForHumans() }}. Datang ke ruangan tes tepat waktu; pengawas LPK memulai tes bersama.</p>
                            @elseif ($exam->isWaitingForStart())
                                <p class="font-semibold text-amber-700">Menunggu pengawas LPK memulai tes di ruangan.</p>
                            @endif
                        </div>
                        @if ($exam->isRunning() || $exam->isWaitingForStart() || $attempt->isSubmitted())
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
                    @if ($rejectedPayment)
                        <div class="mb-4 rounded-2xl bg-red-50 p-3 text-sm text-red-700 ring-1 ring-red-200">
                            Bukti transfer Anda ({{ $rejectedPayment->created_at->translatedFormat('d M Y H:i') }}) ditolak admin: {{ $rejectedPayment->note }}. Silakan daftar ulang dan kirim bukti baru.
                        </div>
                    @endif
                    <form method="POST" action="{{ route('worker.sessions.register', $exam) }}" enctype="multipart/form-data" class="space-y-4"
                          x-data="{ confirmed: false, manual: {{ $errors->has('proof') || ! $qrisAvailable ? 'true' : 'false' }} }">
                        @csrf
                        @if ($exam->positions->isNotEmpty())
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Pilih posisi yang Anda lamar</div>
                                <p class="text-xs text-slate-500">Syarat pendidikan, umur, dan pengalaman diperiksa otomatis dari profil Anda. Peringkat dan kuota dihitung per posisi.</p>
                                <div class="mt-3 grid gap-2">
                                    @foreach ($exam->positions as $position)
                                        @php($unmet = $positionChecks[$position->id] ?? [])
                                        <label class="flex items-start gap-3 rounded-2xl border p-4 text-sm {{ $unmet ? 'border-slate-200 bg-slate-50 opacity-70' : 'border-slate-200 hover:bg-blue-50/50 cursor-pointer' }}">
                                            <input type="radio" name="exam_position_id" value="{{ $position->id }}" class="mt-0.5 text-blue-700" required
                                                @disabled($unmet) @checked((int) old('exam_position_id') === $position->id || ($exam->positions->count() === 1 && ! $unmet))>
                                            <span class="flex-1">
                                                <span class="font-semibold text-slate-900">{{ $position->position }}</span>
                                                <span class="text-slate-500">· {{ $position->quota }} orang</span>
                                                <span class="block text-xs text-slate-500">{{ $position->requirementsSummary() }}@if ($position->notes) · {{ $position->notes }}@endif</span>
                                                @if ($unmet)
                                                    <span class="mt-1 block text-xs font-semibold text-red-600">Belum memenuhi: {{ implode('; ', $unmet) }}</span>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error :messages="$errors->get('exam_position_id')" class="mt-2" />
                            </div>
                        @endif
                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4 text-sm">
                            <input type="checkbox" name="qualification_confirmed" value="1" class="mt-0.5 rounded text-blue-700" x-model="confirmed" @checked(old('qualification_confirmed'))>
                            <span>
                                Saya sudah membaca dan <strong>memenuhi kualifikasi</strong> posisi yang saya pilih{{ $exam->positions->isEmpty() ? ' ('.$exam->position.')' : '' }}.
                                Data profil saya (pendidikan, tanggal lahir, pengalaman kerja) benar.
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
                            <button type="button" @click="manual = ! manual" x-show="! manual"
                                class="rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                                Bayar Manual (Transfer)
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('pay_with')" />

                        <div x-show="manual" x-cloak class="rounded-2xl border border-slate-200 p-4 text-sm">
                            <div class="font-semibold text-slate-900">Bayar manual & unggah bukti transfer</div>
                            <div class="mt-3 grid sm:grid-cols-2 gap-4 items-center">
                                <div class="text-center">
                                    @if (file_exists(public_path(config('careja.qris_image'))))
                                        <img src="{{ asset(config('careja.qris_image')) }}" alt="QRIS {{ config('careja.qris_merchant_name') }}" class="mx-auto max-w-48 rounded-2xl ring-1 ring-slate-200">
                                    @else
                                        <div class="mx-auto w-40 h-40 border-2 border-dashed border-slate-300 rounded-2xl flex items-center justify-center text-xs text-slate-400 p-3">
                                            Gambar QRIS statis belum dipasang (public/{{ config('careja.qris_image') }})
                                        </div>
                                    @endif
                                </div>
                                <div class="space-y-3">
                                    <ol class="list-decimal ms-5 space-y-1 text-slate-700">
                                        <li>Bayar <strong>Rp {{ number_format($fee, 0, ',', '.') }}</strong> ke QRIS {{ config('careja.qris_merchant_name') }}.</li>
                                        <li>Unggah screenshot / bukti pembayaran.</li>
                                        <li>Admin memverifikasi, lalu Anda terdaftar otomatis.</li>
                                    </ol>
                                    <input type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png"
                                           class="block w-full text-sm file:mr-3 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                                    <x-input-error :messages="$errors->get('proof')" />
                                    <button type="submit" name="pay_with" value="manual" :disabled="! confirmed"
                                        class="rounded-full bg-blue-700 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:opacity-40">
                                        Kirim Bukti & Daftar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </x-card>

            @elseif (! $exam->hasEnded())
                <div class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">{{ $registerCheck->message() }}</div>
            @endif

            @if ($exam->hasEnded())
                @foreach ($rankingGroups as $group)
                    <x-card :title="$rankingGroups->count() > 1 ? 'Peringkat Final · '.$group['label'] : 'Peringkat Final'">
                        @php($myRank = $group['ranking']->firstWhere('user_id', auth()->id())?->rank)
                        @if ($myRank)
                            <p class="mb-4 text-sm text-slate-700">Peringkat Anda: <strong>#{{ $myRank }}</strong> dari {{ $group['ranking']->count() }} peserta lulus (kuota {{ $group['quota'] }} orang).</p>
                        @endif
                        <x-session-ranking :ranking="$group['ranking']" :interviews="$interviews->where('exam_position_id', $group['position']?->id)" :quota="$group['quota']" mask :highlight="auth()->id()" />
                    </x-card>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>
