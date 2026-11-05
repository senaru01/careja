{{--
    Pay by transfer / static QRIS and upload the proof; the admin verifies it.
    $action: form URL · $amount: Rupiah · $open: expanded by default (e.g. when QRIS is not available)
    $note: what happens after verification · $fields (optional): extra hidden inputs as name => value
--}}
@php($qrisImage = config('careja.qris_image'))
@php($merchant = config('careja.qris_merchant_name'))

<details class="panel p-6" @if (($open ?? false) || $errors->has('proof')) open @endif>
    <summary class="cursor-pointer text-sm font-semibold text-slate-700">
        {{ ($open ?? false) ? 'Bayar manual & unggah bukti transfer' : 'Atau bayar manual & unggah bukti transfer' }}
    </summary>

    <div class="mt-5 grid sm:grid-cols-2 gap-6 items-center">
        <div class="text-center">
            @if (file_exists(public_path($qrisImage)))
                <img src="{{ asset($qrisImage) }}" alt="QRIS {{ $merchant }}" class="mx-auto max-w-56 rounded-2xl ring-1 ring-slate-200">
            @else
                <div class="mx-auto w-48 h-48 border-2 border-dashed border-slate-300 rounded-2xl flex items-center justify-center text-xs text-slate-400 p-4">
                    Gambar QRIS statis belum dipasang (public/{{ $qrisImage }})
                </div>
            @endif
        </div>
        <div class="space-y-3 text-sm">
            <ol class="list-decimal ms-5 space-y-1 text-slate-700">
                <li>Bayar <strong>Rp {{ number_format($amount, 0, ',', '.') }}</strong> ke QRIS {{ $merchant }}.</li>
                <li>Unggah screenshot / bukti pembayaran (JPG, PNG, atau PDF).</li>
                <li>Admin memverifikasi. {{ $note }}</li>
            </ol>
            <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                @foreach ($fields ?? [] as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <input type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png" required
                       class="block w-full text-sm file:mr-3 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                <x-input-error :messages="$errors->get('proof')" />
                <x-secondary-button type="submit">Kirim Bukti</x-secondary-button>
            </form>
        </div>
    </div>
</details>
