<x-guest-layout>
    <x-slot name="title">Daftar</x-slot>
    <x-slot name="subtitle">Buat akun pencari kerja. Akun langsung aktif setelah daftar.</x-slot>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="name" value="Nama lengkap" />
            <x-text-input id="name" class="block mt-1.5 w-full py-3" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1.5 w-full py-3" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="password" value="Password" />
                <x-text-input id="password" class="block mt-1.5 w-full py-3" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" value="Ulangi password" />
                <x-text-input id="password_confirmation" class="block mt-1.5 w-full py-3" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <x-primary-button class="w-full py-3">Daftar</x-primary-button>

        <p class="text-center text-sm text-slate-500">
            Sudah punya akun? <a class="font-semibold text-blue-700 hover:underline" href="{{ route('login') }}">Masuk</a>
        </p>
    </form>
</x-guest-layout>
