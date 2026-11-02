<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-favicons />
        <title>{{ config('app.name') }} — Cari Kerja yang Transparan</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @php
        $rupiah = fn (string $key): string => 'Rp'.number_format(config($key), 0, ',', '.');
    @endphp
    <body class="font-sans antialiased overflow-x-hidden">
        <x-batik.parang class="h-2 text-blue-800/70" />

        <header class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 py-5 flex justify-between items-center">
            <a href="/" class="inline-flex items-center rounded-full bg-white px-4 py-1.5 shadow-sm ring-1 ring-slate-200/60"><x-application-logo class="h-8" /></a>
            <nav class="flex gap-3">
                @auth
                    <a href="{{ route('dashboard') }}"><x-primary-button type="button">Dashboard</x-primary-button></a>
                @else
                    <a href="{{ route('login') }}"><x-secondary-button>Masuk</x-secondary-button></a>
                    <a href="{{ route('register') }}" class="hidden sm:inline-flex"><x-primary-button type="button">Daftar Pencari Kerja</x-primary-button></a>
                @endauth
            </nav>
        </header>

        <main>
            {{-- Hero --}}
            <section class="relative">
                <x-batik.mega-mendung class="absolute -left-24 top-10 w-[28rem] text-blue-500/15 hidden md:block" />
                <x-batik.mega-mendung class="absolute right-0 -top-6 w-72 text-blue-400/20 -scale-x-100" />

                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 pt-8 pb-16 lg:pt-14 grid lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/80 px-4 py-1.5 text-xs font-semibold text-blue-800 ring-1 ring-blue-200 backdrop-blur">
                            <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                            Platform rekrutmen transparan Indonesia
                        </span>

                        <h1 class="mt-6">
                            <span class="sr-only">Careja</span>
                            <x-application-logo class="h-32 sm:h-48 lg:h-56" />
                        </h1>

                        <p class="mt-2 text-2xl sm:text-4xl font-semibold leading-tight tracking-tight text-slate-900">
                            Cari Kerja yang <span class="relative whitespace-nowrap text-blue-700"><x-batik.parang class="absolute -bottom-2 left-0 h-2.5 text-blue-500/60" />Transparan</span>, ya di Careja.
                        </p>

                        <p class="mt-6 max-w-xl text-lg text-slate-600">
                            Pencari kerja, LPK, dan perusahaan bertemu di satu tempat. Nilai tes, hasil wawancara,
                            dan evaluasi terbuka untuk semua pihak. Tidak ada lagi seleksi yang tertutup.
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-4">
                            @guest
                                <a href="{{ route('register') }}"><x-primary-button type="button" class="px-8 py-3.5 text-base">Daftar Sekarang</x-primary-button></a>
                                <a href="{{ route('login') }}" class="text-sm font-semibold text-blue-800 hover:underline">Sudah punya akun? Masuk →</a>
                            @else
                                <a href="{{ route('dashboard') }}"><x-primary-button type="button" class="px-8 py-3.5 text-base">Buka Dashboard</x-primary-button></a>
                            @endguest
                        </div>

                        <dl class="mt-10 grid grid-cols-3 max-w-md gap-6">
                            @foreach ([[$rupiah('careja.registration_fee'), 'keanggotaan / tahun'], ['100%', 'nilai terbuka'], ['3', 'pihak terhubung']] as [$value, $label])
                                <div>
                                    <dt class="text-2xl font-bold text-slate-900">{{ $value }}</dt>
                                    <dd class="text-xs text-slate-500">{{ $label }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>

                    {{-- Illustration card --}}
                    <div class="relative isolate">
                        <div class="relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-blue-500 via-blue-700 to-blue-950 p-6 sm:p-10 shadow-soft">
                            <x-batik.kawung class="text-white/[0.09]" size="44" />
                            <x-batik.mega-mendung class="absolute -right-10 -top-6 w-64 text-white/20" />
                            <x-batik.mega-mendung class="absolute -left-14 bottom-24 w-56 text-blue-300/20 -scale-x-100" />

                            <div class="relative flex items-center justify-between text-white">
                                <span class="rounded-full bg-white/15 px-4 py-1.5 text-xs font-semibold ring-1 ring-white/30 backdrop-blur">Lowongan terbuka</span>
                                <span class="text-xs text-blue-100">Pekerja · LPK · Perusahaan</span>
                            </div>

                            <x-job-seekers-illustration class="relative mt-6 text-white" />

                            <div class="relative mt-6 grid grid-cols-3 gap-3 text-center text-white">
                                @foreach (['Tes Online' => 'Peringkat terbuka', 'Wawancara' => 'Hasil tercatat', 'Medical' => 'Tahap akhir'] as $title => $text)
                                    <div class="rounded-2xl bg-white/10 px-2 py-3 ring-1 ring-white/20 backdrop-blur">
                                        <div class="text-sm font-semibold">{{ $title }}</div>
                                        <div class="text-[11px] text-blue-100">{{ $text }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="absolute -bottom-4 -right-4 -z-10 h-full w-full rounded-[2.5rem] bg-blue-300/30"></div>
                    </div>
                </div>
            </section>

            <x-batik.parang class="h-6 text-blue-700/15" />

            {{-- Steps --}}
            <section class="max-w-7xl mx-auto px-4 sm:px-6 py-16">
                <div class="text-sm font-semibold text-blue-700">Alur di Careja</div>
                <h2 class="mt-2 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Enam langkah menuju kerja</h2>
                <p class="mt-3 max-w-2xl text-slate-600">Setiap sesi tes diumumkan lengkap sejak awal: perusahaan tujuan, posisi, kualifikasi, dan jumlah orang yang dibutuhkan. Jadi Anda hanya membayar tes yang memang sesuai keahlian Anda.</p>

                <div class="mt-8 grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach ([
                        ['01', 'Daftar & Lengkapi Profil', 'Isi data diri, pendidikan, skill, pengalaman kerja, foto, dan unggah CV.'],
                        ['02', 'Aktifkan Keanggotaan', $rupiah('careja.registration_fee').' via QRIS, aktif 1 tahun. Anda akan menerima kabar setiap ada sesi tes baru.'],
                        ['03', 'Daftar Sesi Tes', 'Pilih sesi yang sesuai kualifikasi, nyatakan Anda memenuhinya, lalu bayar '.$rupiah('careja.exam_registration_fee').' per sesi.'],
                        ['04', 'Tes Online & Peringkat', 'Nilai dihitung otomatis. Setelah sesi ditutup, peringkat peserta terbuka untuk semua pihak.'],
                        ['05', 'Wawancara Perusahaan', 'Perusahaan memilih sesuai peringkat dan kuota. Melewati peringkat yang lebih tinggi wajib disertai alasan.'],
                        ['06', 'Medical Checkup', 'Penilaian akhir bagi yang lulus wawancara. Jika tidak lolos, kursinya diisi kandidat lain dari sesi yang sama.'],
                    ] as [$number, $title, $text])
                        <div class="panel relative overflow-hidden p-6">
                            <x-batik.kawung class="text-blue-600/[0.07] [mask-image:linear-gradient(to_bottom_left,black,transparent_60%)]" size="36" />
                            <div class="relative text-4xl font-extrabold text-blue-700">{{ $number }}</div>
                            <div class="relative mt-4 font-semibold text-slate-900">{{ $title }}</div>
                            <p class="relative mt-1 text-sm text-slate-500">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Parties --}}
            <section class="max-w-7xl mx-auto px-4 sm:px-6 pb-20">
                <div class="relative overflow-hidden rounded-[2.5rem] bg-blue-950 p-8 sm:p-12 text-white">
                    <x-batik.kawung class="text-blue-400/[0.12]" size="56" />
                    <x-batik.mega-mendung class="absolute -right-16 -bottom-10 w-96 text-blue-400/20" />

                    <h2 class="relative text-3xl sm:text-4xl font-semibold tracking-tight max-w-2xl">Menguntungkan semua pihak, memutus rantai monopoli.</h2>
                    <div class="relative mt-8 grid md:grid-cols-3 gap-5 text-sm">
                        @foreach ([
                            ['Pencari Kerja', 'Tahu sejak awal kebutuhan perusahaan, sehingga tidak membayar tes yang di luar keahlian. Jika jadwal dimundurkan, bisa keluar sesi dan mendapat token untuk sesi lain.'],
                            ['LPK', 'Menerbitkan sesi tes cukup '.$rupiah('careja.exam_session_fee').' per sesi. Soal dan kuota terkunci setelah terbit, nilai tidak dapat diubah.'],
                            ['Perusahaan', 'Melihat jumlah peserta siap tes, memilih dari peringkat yang sama dengan yang dilihat semua pihak, sampai medical checkup.'],
                        ] as [$title, $text])
                            <div class="rounded-3xl bg-white/[0.06] p-6 ring-1 ring-white/10 backdrop-blur">
                                <div class="text-lg font-semibold">{{ $title }}</div>
                                <p class="mt-2 text-blue-100/80">{{ $text }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </main>

        <footer class="relative">
            <x-batik.parang class="h-3 text-blue-800/40" />
            <div class="py-6 text-center text-xs text-slate-500">&copy; {{ date('Y') }} Careja · Cari Kerja yang Transparan, ya di Careja.</div>
        </footer>
    </body>
</html>
