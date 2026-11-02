{{-- Live QRIS payment. Expects $payment (pending Midtrans Payment) and $caption (what is being paid). --}}
<div class="panel relative overflow-hidden p-6 sm:p-8"
     x-data="qrisWaiter(@js(route('payments.status', $payment)), {{ $payment->payment_due_at?->getTimestampMs() ?? 0 }})"
     x-init="start()">
    <x-batik.kawung class="text-blue-600/[0.05]" size="40" />

    <div class="relative grid sm:grid-cols-2 gap-8 items-center">
        <div class="text-center">
            <div class="mx-auto w-fit rounded-3xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
                <img src="{{ $payment->qr_url }}" alt="Kode QRIS pembayaran Careja" class="h-60 w-60">
            </div>
            <div class="mt-3 text-xs text-slate-500">Order {{ $payment->gateway_order_id }}</div>
        </div>

        <div class="space-y-4">
            <div>
                <div class="text-sm text-slate-500">Total bayar</div>
                <div class="text-4xl font-semibold tracking-tight text-slate-900">Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>
                <div class="text-sm text-slate-500">{{ $caption }}</div>
            </div>

            <div class="rounded-2xl bg-blue-50 p-4">
                <div class="text-xs text-blue-800">Selesaikan pembayaran dalam</div>
                <div class="text-3xl font-mono font-semibold" :class="remaining < 60000 ? 'text-red-600' : 'text-blue-900'" x-text="label"></div>
            </div>

            <ol class="list-decimal ms-5 space-y-1 text-sm text-slate-700">
                <li>Buka m-banking atau e-wallet (GoPay, OVO, DANA, ShopeePay, dll).</li>
                <li>Scan kode QR di samping, pastikan nominal Rp {{ number_format($payment->amount, 0, ',', '.') }}.</li>
                <li>Setelah berhasil, status diperbarui <strong>otomatis</strong>. Halaman ini akan memuat ulang sendiri.</li>
            </ol>

            <div class="flex items-center gap-2 text-sm text-slate-500">
                <span class="h-2.5 w-2.5 rounded-full bg-amber-400 animate-pulse"></span>
                <span x-text="message">Menunggu pembayaran...</span>
            </div>
        </div>
    </div>
</div>

@once
    <script>
        function qrisWaiter(statusUrl, dueAt) {
            return {
                remaining: Math.max(0, dueAt - Date.now()),
                label: '--:--',
                message: 'Menunggu pembayaran...',
                start() {
                    const tick = () => {
                        this.remaining = Math.max(0, dueAt - Date.now());
                        const minutes = Math.floor(this.remaining / 60000);
                        const seconds = Math.floor((this.remaining % 60000) / 1000);
                        this.label = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                    };
                    tick();
                    setInterval(tick, 1000);

                    const poll = async () => {
                        try {
                            const response = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
                            if (response.ok) {
                                const data = await response.json();
                                if (data.status !== 'pending') {
                                    this.message = data.status === 'verified' ? 'Pembayaran berhasil! Memuat ulang...' : 'Status berubah, memuat ulang...';
                                    window.location.reload();
                                    return;
                                }
                            }
                        } catch (error) {
                            // Network hiccup: try again on the next round.
                        }
                        setTimeout(poll, 5000);
                    };
                    setTimeout(poll, 5000);
                },
            };
        }
    </script>
@endonce
