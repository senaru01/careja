@php
    $user = Auth::user();
@endphp

<nav x-data="{ open: false }" class="border-b border-slate-300/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Top bar: brand pill, role pill, logout & avatar --}}
        <div class="flex justify-between items-center pt-4">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-full bg-white px-4 py-1.5 shadow-sm ring-1 ring-slate-200/60"><x-application-logo class="h-8" /></a>

            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-flex items-center rounded-full bg-blue-900 px-4 py-2 text-sm font-semibold text-white ring-4 ring-white/70">
                    {{ $user->role->label() }}
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Keluar"
                        class="h-10 w-10 inline-flex items-center justify-center rounded-full bg-white text-slate-600 shadow-sm ring-1 ring-slate-200/60 hover:text-blue-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H2.25" />
                        </svg>
                    </button>
                </form>

                <a href="{{ $user->isWorker() ? route('worker.profile.edit') : route('profile.edit') }}" title="{{ $user->name }}" class="rounded-full ring-4 ring-white/70">
                    <x-avatar :user="$user" class="!bg-gradient-to-br from-blue-500 to-blue-900 !text-white" />
                </a>

                <button @click="open = ! open" class="sm:hidden h-10 w-10 inline-flex items-center justify-center rounded-full bg-white text-slate-600 ring-1 ring-slate-200/60">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="hidden sm:flex gap-7 mt-5">
            @include('layouts.nav-links', ['component' => 'nav-link'])
        </div>

        {{-- Mobile menu --}}
        <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden py-3 space-y-1">
            @include('layouts.nav-links', ['component' => 'responsive-nav-link'])
            <x-responsive-nav-link :href="route('profile.edit')">Akun Saya</x-responsive-nav-link>
        </div>
    </div>
</nav>
