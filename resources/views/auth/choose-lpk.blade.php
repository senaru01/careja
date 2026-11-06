<x-guest-layout>
    <x-slot name="title">Daftar Lewat LPK Anda</x-slot>
    <x-slot name="subtitle">Setiap pencari kerja menjadi member satu LPK. Pilih LPK Anda, lalu daftar di alamat LPK tersebut.</x-slot>

    @if ($lpks->isEmpty())
        <p class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">Belum ada LPK mitra yang membuka pendaftaran. Silakan cek kembali nanti.</p>
    @else
        <ul class="space-y-3">
            @foreach ($lpks as $lpk)
                <li>
                    <a href="{{ $lpk->url('register') }}" class="flex items-center justify-between gap-4 rounded-2xl bg-white p-4 ring-1 ring-slate-200 transition hover:ring-blue-400">
                        <span>
                            <span class="block font-semibold text-slate-900">{{ $lpk->name }}</span>
                            <span class="block text-sm text-slate-500">{{ $lpk->host() }}</span>
                        </span>
                        <span class="text-sm font-semibold text-blue-700">Daftar &rarr;</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    <p class="mt-6 text-center text-sm text-slate-500">
        Sudah punya akun? Login di alamat LPK Anda.
    </p>
</x-guest-layout>
