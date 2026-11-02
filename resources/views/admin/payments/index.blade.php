<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Verifikasi Pembayaran</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <div class="flex gap-2">
                @foreach ($statuses as $option)
                    <a href="{{ route('admin.payments.index', ['status' => $option->value]) }}"
                       class="px-4 py-2 rounded-full text-sm font-medium {{ $status === $option ? 'bg-blue-900 text-white' : 'bg-white/80 text-slate-600 hover:bg-white' }}">
                        {{ $option->label() }}
                    </a>
                @endforeach
                <a href="{{ route('admin.payments.index', ['status' => 'all']) }}"
                   class="px-4 py-2 rounded-full text-sm font-medium {{ $status === null ? 'bg-blue-900 text-white' : 'bg-white/80 text-slate-600 hover:bg-white' }}">Semua</a>
            </div>

            <div class="panel overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Pembayar</th>
                            <th class="px-4 py-3">Nominal</th>
                            <th class="px-4 py-3">Dikirim</th>
                            <th class="px-4 py-3">Bukti</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($payments as $payment)
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $payment->user->name }}</div>
                                    <div class="text-slate-500">{{ $payment->user->email }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                    <div class="text-xs text-slate-500">{{ $payment->purpose->label() }}@if ($payment->exam) · {{ $payment->exam->title }}@endif</div>
                                </td>
                                <td class="px-4 py-3">{{ $payment->created_at->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    @if ($payment->isMidtrans())
                                        <x-badge color="blue">QRIS otomatis</x-badge>
                                        <div class="text-xs text-slate-400 mt-1">{{ $payment->gateway_order_id }}</div>
                                    @else
                                        <a class="text-blue-700 underline" target="_blank" href="{{ route('admin.payments.proof', $payment) }}">Lihat bukti</a>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <x-badge :color="match ($payment->status->value) { 'verified' => $payment->isActive() ? 'green' : 'gray', 'rejected', 'expired' => 'red', default => 'yellow' }">
                                        @if ($payment->status->value === 'verified' && ! $payment->isActive())
                                            Masa aktif habis
                                        @elseif ($payment->isPending() && $payment->isMidtrans())
                                            Menunggu Pembayaran
                                        @else
                                            {{ $payment->status->label() }}
                                        @endif
                                    </x-badge>
                                    @if ($payment->isMidtrans() && $payment->status->value === 'verified')
                                        <div class="text-xs text-slate-500 mt-1">dikonfirmasi Midtrans</div>
                                    @endif
                                    @if ($payment->expires_at)
                                        <div class="text-xs text-slate-500 mt-1">aktif s.d. {{ $payment->expires_at->format('d M Y') }}</div>
                                    @endif
                                    @if ($payment->verifier)
                                        <div class="text-xs text-slate-500 mt-1">oleh {{ $payment->verifier->name }}</div>
                                    @endif
                                    @if ($payment->note) <div class="text-xs text-slate-500">{{ $payment->note }}</div> @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($payment->isPending() && $payment->isMidtrans())
                                        <span class="text-xs text-slate-500">Menunggu konfirmasi otomatis dari Midtrans</span>
                                    @elseif ($payment->isPending())
                                        <div class="flex flex-col gap-2">
                                            <form method="POST" action="{{ route('admin.payments.update', $payment) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="verified">
                                                <x-primary-button>Verifikasi</x-primary-button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.payments.update', $payment) }}" class="flex gap-2">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="rejected">
                                                <x-text-input name="note" class="text-xs py-1" placeholder="Alasan penolakan" required />
                                                <x-danger-button>Tolak</x-danger-button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Tidak ada data pembayaran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-input-error :messages="$errors->get('note')" />
            {{ $payments->links() }}
        </div>
    </div>
</x-app-layout>
