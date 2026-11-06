<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Keanggotaan & Pembayaran</h2>
    </x-slot>

    @php($membershipLabel = $membershipMonths == 12 ? '1 tahun' : $membershipMonths.' bulan')

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            @include('worker.partials.membership-status')

            @if ($pendingQris)
                @include('payments.partials.qris-waiter', ['payment' => $pendingQris, 'caption' => 'Keanggotaan '.$membershipLabel])
            @elseif (! $profileComplete)
                <x-card title="Aktifkan Akun">
                    <div class="text-4xl font-semibold tracking-tight text-slate-900">Rp {{ number_format($fee, 0, ',', '.') }}</div>
                    <div class="text-sm text-slate-500">berlaku {{ $membershipLabel }}</div>
                    <p class="mt-4 text-sm text-amber-800">
                        Lengkapi <a class="font-semibold underline" href="{{ route('worker.profile.edit') }}">profil kandidat dan CV</a> terlebih dahulu sebelum membayar.
                    </p>
                </x-card>
            @elseif ($activeMembership)
                {{-- Nothing to pay: the membership banner above shows the status. --}}
            @elseif (! $canUpload)
                <x-card title="Pembayaran Diproses">
                    <p class="text-sm text-slate-600">Bukti pembayaran Anda sudah terkirim dan sedang diverifikasi {{ $paysLpkDirectly ? $lpk->name : 'admin' }}.</p>
                </x-card>
            @else
                {{-- Choose how to pay --}}
                <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-blue-600 via-blue-800 to-blue-950 p-6 sm:p-8 text-white shadow-soft">
                    <x-batik.kawung class="text-white/[0.08]" size="40" />
                    <x-batik.mega-mendung class="absolute -right-12 -top-6 w-64 text-white/15" />

                    <div class="relative flex flex-wrap items-end justify-between gap-6">
                        <div>
                            <div class="text-sm text-blue-100">{{ $membershipExpired ? 'Perpanjang keanggotaan' : 'Aktifkan akun Anda' }}</div>
                            <div class="mt-1 text-5xl font-extrabold tracking-tight">Rp {{ number_format($fee, 0, ',', '.') }}</div>
                            <div class="text-sm text-blue-100">berlaku {{ $membershipLabel }} · biaya ikut tes per sesi Rp {{ number_format($examFee, 0, ',', '.') }}</div>
                        </div>

                        @if ($qrisAvailable)
                            <form method="POST" action="{{ route('worker.payment.qris.store') }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-white px-7 py-3.5 text-base font-semibold text-blue-900 shadow-sm transition hover:bg-blue-50">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" /></svg>
                                    Bayar dengan QRIS
                                </button>
                            </form>
                        @endif
                    </div>

                    @if ($qrisAvailable)
                        <p class="relative mt-4 text-xs text-blue-100">Bisa dibayar dengan semua m-banking & e-wallet. Akun aktif otomatis begitu pembayaran berhasil, tanpa menunggu verifikasi admin.</p>
                    @endif
                </div>

                {{-- Manual fallback: static QRIS + proof upload --}}
                <details class="panel p-6" @if (! $qrisAvailable || $errors->has('proof')) open @endif>
                    <summary class="cursor-pointer text-sm font-semibold text-slate-700">
                        {{ $qrisAvailable ? 'Atau bayar manual & unggah bukti transfer' : ($paysLpkDirectly ? 'Transfer ke rekening '.$lpk->name.' & unggah bukti' : 'Bayar via QRIS & unggah bukti') }}
                    </summary>

                    <div class="mt-5 grid sm:grid-cols-2 gap-6 items-center">
                        @if ($paysLpkDirectly)
                            <div class="rounded-2xl bg-blue-50 p-5 text-sm ring-1 ring-blue-100">
                                @if ($lpk->hasBankAccount())
                                    <div class="text-slate-500">Bank</div>
                                    <div class="font-semibold text-slate-900">{{ $lpk->bank_name }}</div>
                                    <div class="mt-3 text-slate-500">Nomor rekening</div>
                                    <div class="text-2xl font-bold tracking-wide text-blue-900">{{ $lpk->bank_account_number }}</div>
                                    <div class="mt-3 text-slate-500">Atas nama</div>
                                    <div class="font-semibold text-slate-900">{{ $lpk->bank_account_holder ?: $lpk->name }}</div>
                                @else
                                    <p class="text-amber-800">Rekening {{ $lpk->name }} belum diisi. Silakan hubungi LPK Anda.</p>
                                @endif
                            </div>
                        @else
                        <div class="text-center">
                            @if (file_exists(public_path($qrisImage)))
                                <img src="{{ asset($qrisImage) }}" alt="QRIS {{ $qrisMerchant }}" class="mx-auto max-w-56 rounded-2xl ring-1 ring-slate-200">
                            @else
                                <div class="mx-auto w-48 h-48 border-2 border-dashed border-slate-300 rounded-2xl flex items-center justify-center text-xs text-slate-400 p-4">
                                    Gambar QRIS statis belum dipasang (public/{{ $qrisImage }})
                                </div>
                            @endif
                        </div>
                        @endif
                        <div class="space-y-3 text-sm">
                            <ol class="list-decimal ms-5 space-y-1 text-slate-700">
                                @if ($paysLpkDirectly)
                                    <li>Transfer Rp {{ number_format($fee, 0, ',', '.') }} ke rekening {{ $lpk->name }}.</li>
                                    <li>Unggah screenshot / bukti transfer.</li>
                                    <li>{{ $lpk->name }} memverifikasi, lalu akun aktif.</li>
                                @else
                                    <li>Bayar Rp {{ number_format($fee, 0, ',', '.') }} ke QRIS {{ $qrisMerchant }}.</li>
                                    <li>Unggah screenshot / bukti pembayaran.</li>
                                    <li>Admin memverifikasi, lalu akun aktif.</li>
                                @endif
                            </ol>
                            <form method="POST" action="{{ route('worker.payment.store') }}" enctype="multipart/form-data" class="space-y-3">
                                @csrf
                                <input id="proof" type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png" required
                                       class="block w-full text-sm file:mr-3 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                                <x-input-error :messages="$errors->get('proof')" />
                                <x-secondary-button type="submit">Kirim Bukti</x-secondary-button>
                            </form>
                        </div>
                    </div>
                </details>
            @endif

            @if ($payments->isNotEmpty())
                <x-card title="Riwayat Pembayaran">
                    <ul class="divide-y divide-slate-100 text-sm">
                        @foreach ($payments as $payment)
                            <li class="py-3 flex flex-wrap justify-between gap-3">
                                <span>
                                    {{ $payment->created_at->format('d M Y H:i') }} · Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                    <span class="text-slate-400">· {{ $payment->purpose->label() }}@if ($payment->exam) ({{ $payment->exam->title }})@endif · {{ $payment->method->label() }}</span>
                                </span>
                                <span class="flex flex-wrap items-center gap-2">
                                    <x-badge :color="match ($payment->status->value) { 'verified' => $payment->isActive() || ! $payment->isMembership() ? 'green' : 'gray', 'rejected', 'expired' => 'red', default => 'yellow' }">
                                        @if ($payment->status->value === 'verified' && $payment->isMembership() && ! $payment->isActive())
                                            Masa aktif habis
                                        @elseif ($payment->isPending() && $payment->isMidtrans())
                                            Menunggu Pembayaran
                                        @else
                                            {{ $payment->status->label() }}
                                        @endif
                                    </x-badge>
                                    @if ($payment->expires_at)
                                        <span class="text-slate-500">berlaku s.d. {{ $payment->expires_at->format('d M Y') }}</span>
                                    @endif
                                    @if ($payment->note) <span class="text-slate-500">— {{ $payment->note }}</span> @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>
