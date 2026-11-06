<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-favicons />
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Careja') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <x-batik.parang class="fixed inset-x-0 top-0 h-2 text-blue-800/70" />
        <x-batik.mega-mendung class="fixed -left-20 bottom-10 w-96 text-blue-500/10" />
        <x-batik.mega-mendung class="fixed -right-10 top-16 w-72 text-blue-500/10 -scale-x-100" />

        <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-8">
            <div class="w-full max-w-4xl grid md:grid-cols-2 overflow-hidden rounded-[2rem] shadow-soft ring-1 ring-white/70">
                {{-- Brand panel --}}
                <div class="relative hidden md:flex flex-col justify-between overflow-hidden bg-gradient-to-br from-blue-600 via-blue-800 to-blue-900 p-8 text-white">
                    <x-batik.kawung class="text-white/[0.08]" size="40" />
                    <x-batik.mega-mendung class="absolute -right-16 bottom-16 w-80 text-white/15" />

                    <a href="/" class="relative inline-flex w-fit items-center rounded-full bg-white/15 px-4 py-2 text-sm font-semibold ring-1 ring-white/30 backdrop-blur">&larr; Beranda</a>

                    <div class="relative">
                        <div class="inline-flex rounded-3xl bg-white px-5 py-4 shadow-soft"><x-application-logo class="h-20" /></div>
                        @if ($tenant)
                            <div class="mt-5 inline-flex rounded-full bg-white/15 px-4 py-1.5 text-sm font-semibold ring-1 ring-white/30">{{ $tenant->name }}</div>
                        @endif
                        <h2 class="mt-5 text-2xl font-semibold leading-tight tracking-tight">Cari Kerja yang Transparan, ya di Careja.</h2>
                        <p class="mt-3 text-sm text-blue-100">Pencari kerja, LPK, dan perusahaan melihat hasil seleksi yang sama. Sistem akan mengarahkan Anda ke menu sesuai hak akses.</p>
                    </div>

                    <x-batik.parang class="absolute inset-x-0 bottom-0 h-2.5 text-white/30" />
                </div>

                {{-- Form panel --}}
                <div class="bg-slate-50/95 p-8 sm:p-10">
                    <a href="/" class="mb-6 inline-block md:hidden"><x-application-logo class="h-12" /></a>
                    @if ($tenant)
                        <div class="mb-4 text-sm font-semibold text-blue-800 md:hidden">{{ $tenant->name }}</div>
                    @endif
                    @isset($title)
                        <h1 class="text-2xl font-semibold text-slate-900">{{ $title }}</h1>
                    @endisset
                    @isset($subtitle)
                        <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
                    @endisset

                    <div class="{{ isset($title) ? 'mt-6' : '' }}">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
