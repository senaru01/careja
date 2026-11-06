<x-guest-layout>
    <x-slot name="title">Masuk</x-slot>
    <x-slot name="subtitle">
        @if ($tenant)
            Login untuk akun {{ $tenant->name }}.
        @else
            Login admin Careja. Member, LPK, dan perusahaan login lewat alamat masing-masing (contoh: namalpk.{{ config('careja.root_domain') }}).
        @endif
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1.5 w-full py-3" type="email" name="email" :value="old('email')" placeholder="contoh: nama@email.com" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="block mt-1.5 w-full py-3" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500" name="remember">
                <span class="ms-2 text-sm text-slate-600">Ingat saya</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-slate-500 hover:text-blue-700" href="{{ route('password.request') }}">Lupa password?</a>
            @endif
        </div>

        <x-primary-button class="w-full py-3">Masuk</x-primary-button>

        @if (! $tenant?->isCompany())
            <p class="text-center text-sm text-slate-500">
                Belum punya akun? <a class="font-semibold text-blue-700 hover:underline" href="{{ route('register') }}">Daftar sebagai pencari kerja</a>
            </p>
        @endif
    </form>
</x-guest-layout>
