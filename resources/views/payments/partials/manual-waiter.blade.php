{{-- A transfer proof waiting for the admin. $payment: the pending manual payment · $caption: what it pays for. --}}
<div class="rounded-2xl bg-amber-50 p-5 text-sm text-amber-900 ring-1 ring-amber-200">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="font-semibold">Bukti transfer sedang diverifikasi admin</div>
            <div class="mt-0.5">{{ $caption }} · Rp {{ number_format($payment->amount, 0, ',', '.') }} · dikirim {{ $payment->created_at->translatedFormat('d M Y H:i') }}</div>
        </div>
        <x-badge color="yellow">Menunggu Verifikasi</x-badge>
    </div>
    <p class="mt-2 text-xs text-amber-800">Status berubah otomatis setelah admin memverifikasi. Jika ditolak, alasannya akan tampil dan Anda bisa mengirim bukti baru.</p>
</div>
