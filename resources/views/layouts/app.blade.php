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
        <x-batik.parang class="h-2 text-blue-800/70" />

        <div class="relative min-h-screen overflow-x-hidden">
            <x-batik.mega-mendung class="absolute -right-24 top-16 w-[26rem] text-blue-500/10 hidden md:block" />

            @include('layouts.navigation')

            @isset($header)
                <header class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
                    <div class="text-sm text-slate-500">Careja · Cari Kerja yang Transparan</div>
                    <div class="mt-2">{{ $header }}</div>
                </header>
            @endisset

            <main class="pb-12">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
