<x-guest-layout>
    <x-slot name="title">Verifikasi 2 Langkah</x-slot>
    <x-slot name="subtitle">Masukkan kode 6 digit yang kami kirim ke email Anda.</x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('two-factor.challenge') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="code" value="Kode verifikasi" />
            <x-text-input id="code" class="block mt-1.5 w-full py-3 text-center text-2xl font-semibold tracking-[0.5em]" type="text" name="code"
                inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="••••••" required autofocus autocomplete="one-time-code" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <x-primary-button class="w-full py-3">Verifikasi</x-primary-button>
    </form>

    <div class="mt-5 flex items-center justify-between text-sm">
        <form method="POST" action="{{ route('two-factor.resend') }}">
            @csrf
            <button type="submit" class="font-semibold text-blue-700 hover:underline">Kirim ulang kode</button>
        </form>

        <a href="{{ route('login') }}" class="text-slate-500 hover:text-blue-700">Kembali ke login</a>
    </div>
</x-guest-layout>
