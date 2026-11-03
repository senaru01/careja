<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <x-favicons />
        <title>Demo Pembayaran — {{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @php
        $demoKey = config('careja.payment_demo_key');
        $isProduction = (bool) config('services.midtrans.is_production');
        $statusColor = fn ($status) => match ($status) {
            \App\Enums\PaymentStatus::Verified => 'green',
            \App\Enums\PaymentStatus::Pending => 'yellow',
            default => 'red',
        };
        $statusLabel = fn ($status) => match ($status) {
            \App\Enums\PaymentStatus::Verified => 'Berhasil (settlement)',
            \App\Enums\PaymentStatus::Pending => 'Menunggu pembayaran',
            default => $status->label(),
        };
    @endphp
    <body class="font-sans antialiased overflow-x-hidden">
        <x-batik.parang class="h-2 text-blue-800/70" />

        <header class="max-w-6xl mx-auto px-4 sm:px-6 py-5 flex flex-wrap gap-3 justify-between items-center">
            <a href="/" class="inline-flex items-center rounded-full bg-white px-4 py-1.5 shadow-sm ring-1 ring-slate-200/60"><x-application-logo class="h-8" /></a>
            <x-badge :color="$isProduction ? 'green' : 'blue'">Midtrans {{ $isProduction ? 'Production' : 'Sandbox' }} · QRIS</x-badge>
        </header>

        <main class="max-w-6xl mx-auto px-4 sm:px-6 pb-16 space-y-8">
            <div>
                <div class="text-sm font-semibold text-blue-700">Demo Pembayaran</div>
                <h1 class="mt-2 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Uji setiap item katalog pembayaran Careja</h1>
                <p class="mt-3 max-w-3xl text-slate-600">
                    Halaman ini untuk tim peninjau Midtrans. Pilih item, sistem membuat transaksi QRIS asli melalui Midtrans Core API,
                    lalu status diperbarui otomatis lewat notifikasi HTTP (webhook) dan pengecekan status. Transaksi demo tercatat,
                    tetapi tidak mengubah data rekrutmen (tidak mengaktifkan keanggotaan, menerbitkan sesi, dan sebagainya).
                    Harga sama dengan <a href="{{ url('/#biaya') }}" class="font-semibold text-blue-700 hover:underline">katalog pembayaran</a> di halaman utama.
                </p>
            </div>

            <x-flash />

            @unless ($qrisAvailable)
                <div class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">
                    Server key Midtrans belum diatur, sehingga QRIS belum dapat dibuat.
                </div>
            @endunless

            @if ($payment)
                @if ($payment->isPending())
                    @include('payments.partials.qris-waiter', [
                        'payment' => $payment,
                        'caption' => $payment->purpose->label().' · transaksi demo',
                        'statusUrl' => route('payment-demo.status', ['demoKey' => $demoKey, 'payment' => $payment]),
                    ])
                @else
                    <div class="panel p-6 sm:p-8">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $payment->purpose->label() }}</h2>
                            <x-badge :color="$statusColor($payment->status)">{{ $statusLabel($payment->status) }}</x-badge>
                        </div>
                        <dl class="mt-4 grid sm:grid-cols-4 gap-4 text-sm">
                            <div><dt class="text-slate-500">Order ID</dt><dd class="font-mono font-semibold text-slate-900">{{ $payment->gateway_order_id }}</dd></div>
                            <div><dt class="text-slate-500">Nominal</dt><dd class="font-semibold text-slate-900">Rp {{ number_format($payment->amount, 0, ',', '.') }}</dd></div>
                            <div><dt class="text-slate-500">Status Midtrans</dt><dd class="font-semibold text-slate-900">{{ $payment->gateway_status ?? '-' }}</dd></div>
                            <div><dt class="text-slate-500">Waktu konfirmasi</dt><dd class="font-semibold text-slate-900">{{ $payment->verified_at?->format('d M Y H:i:s') ?? '-' }}</dd></div>
                        </dl>
                        <a href="{{ route('payment-demo.index', $demoKey) }}" class="mt-6 inline-block text-sm font-semibold text-blue-700 hover:underline">&larr; Coba item lain</a>
                    </div>
                @endif
            @endif

            <section>
                <h2 class="text-xl font-semibold text-slate-900">Katalog pembayaran</h2>
                <div class="mt-4 grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach ($items as $item)
                        <form method="POST" action="{{ route('payment-demo.store', $demoKey) }}" class="panel relative flex flex-col overflow-hidden p-6">
                            @csrf
                            <input type="hidden" name="purpose" value="{{ $item['purpose']->value }}">
                            <x-batik.kawung class="text-blue-600/[0.07] [mask-image:linear-gradient(to_bottom_left,black,transparent_60%)]" size="36" />
                            <span class="relative self-start rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-800 ring-1 ring-blue-200">{{ $item['payer'] }}</span>
                            <div class="relative mt-4 font-semibold text-slate-900">{{ $item['purpose']->label() }}</div>
                            <div class="relative mt-2">
                                <span class="text-3xl font-extrabold text-blue-700">Rp{{ number_format($item['purpose']->fee(), 0, ',', '.') }}</span>
                                <span class="text-sm text-slate-500">/ {{ $item['unit'] }}</span>
                            </div>
                            <p class="relative mt-2 flex-1 text-sm text-slate-600">{{ $item['description'] }}</p>
                            <div class="relative mt-5">
                                <x-primary-button :disabled="! $qrisAvailable">Bayar via QRIS</x-primary-button>
                            </div>
                        </form>
                    @endforeach
                </div>
            </section>

            <section class="panel overflow-x-auto">
                <div class="px-4 pt-4 font-semibold text-slate-900">Transaksi demo terakhir</div>
                <table class="mt-2 min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Order ID</th>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3">Nominal</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Dibuat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($recentPayments as $recent)
                            <tr>
                                <td class="px-4 py-3 font-mono"><a class="text-blue-700 hover:underline" href="{{ route('payment-demo.show', ['demoKey' => $demoKey, 'payment' => $recent]) }}">{{ $recent->gateway_order_id }}</a></td>
                                <td class="px-4 py-3">{{ $recent->purpose->label() }}</td>
                                <td class="px-4 py-3">Rp {{ number_format($recent->amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3"><x-badge :color="$statusColor($recent->status)">{{ $statusLabel($recent->status) }}</x-badge></td>
                                <td class="px-4 py-3 text-slate-500">{{ $recent->created_at->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Belum ada transaksi demo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </main>

        <footer class="py-6 text-center text-xs text-slate-500">&copy; {{ date('Y') }} Careja · Kontak: <a href="mailto:admin@careja.id" class="text-blue-700 hover:underline">admin@careja.id</a></footer>
    </body>
</html>
