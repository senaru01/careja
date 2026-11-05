<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <a href="{{ auth()->user()->isLpk() ? route('lpk.exams.show', $exam) : route('company.sessions.show', $exam) }}" class="text-sm text-slate-500 hover:text-blue-700">← {{ $exam->title }}</a>
                <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Live Score</h2>
                <div class="text-sm text-slate-500">{{ $exam->position }} · {{ $exam->company?->name }} · {{ $exam->organization->name }}</div>
            </div>
            @if ($canStart)
                <form method="POST" action="{{ route('lpk.exams.start', $exam) }}"
                      onsubmit="return confirm('Mulai tes sekarang untuk semua peserta di ruangan? Waktu {{ $exam->duration_minutes }} menit langsung berjalan.')">
                    @csrf
                    <x-primary-button>Mulai Tes Sekarang</x-primary-button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-8" x-data="liveBoard(@js($board), @js(route('sessions.live.data', $exam)))" x-init="start()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />
            <x-input-error :messages="$errors->get('start')" />

            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                <div class="panel p-5 col-span-2">
                    <div class="text-sm text-slate-500">Status sesi</div>
                    <div class="mt-1 text-2xl font-semibold tracking-tight text-slate-900" x-text="board.phase"></div>
                    <div class="mt-1 text-sm text-slate-500" x-show="board.ends_at && ! board.ended">
                        Sisa waktu <span class="font-mono text-lg font-semibold" :class="remaining < 60000 ? 'text-red-600' : 'text-blue-800'" x-text="clock"></span>
                    </div>
                    <div class="mt-1 text-sm text-slate-500" x-show="! board.started">Peserta menunggu di ruangan. Tekan <strong>Mulai Tes Sekarang</strong> saat semua siap.</div>
                </div>
                <template x-for="(value, label) in board.counts" :key="label">
                    <div class="panel p-5">
                        <div class="text-sm text-slate-500" x-text="label"></div>
                        <div class="mt-1 text-4xl font-semibold tracking-tight text-slate-900" x-text="value"></div>
                    </div>
                </template>
            </div>

            <div class="panel overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Peserta</th>
                            <th class="px-4 py-3">Posisi</th>
                            <th class="px-4 py-3 w-64">Progres</th>
                            <th class="px-4 py-3">Benar</th>
                            <th class="px-4 py-3">Skor</th>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="row in board.participants" :key="row.id">
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-900" x-text="row.rank"></td>
                                <td class="px-4 py-3 font-medium text-slate-900" x-text="row.name"></td>
                                <td class="px-4 py-3 text-slate-600" x-text="row.position ?? '-'"></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 flex-1 rounded-full bg-slate-100">
                                            <div class="h-2 rounded-full bg-blue-600 transition-all" :style="`width: ${board.total_questions ? Math.round(row.answered / board.total_questions * 100) : 0}%`"></div>
                                        </div>
                                        <span class="whitespace-nowrap text-xs text-slate-500" x-text="`${row.answered}/${board.total_questions}`"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3" x-text="row.correct"></td>
                                <td class="px-4 py-3 text-lg font-semibold" :class="row.passed === false ? 'text-red-600' : (row.passed ? 'text-emerald-700' : 'text-slate-900')" x-text="row.score"></td>
                                <td class="px-4 py-3 text-slate-500" x-text="row.duration ?? '-'"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                          :class="{
                                              'bg-emerald-50 text-emerald-700': row.status === 'submitted' && row.passed,
                                              'bg-red-50 text-red-600': row.status === 'submitted' && ! row.passed,
                                              'bg-amber-50 text-amber-700': row.status === 'working',
                                              'bg-slate-100 text-slate-600': row.status === 'waiting' || row.status === 'absent',
                                          }" x-text="row.status_label"></span>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="board.participants.length === 0">
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">Belum ada peserta terdaftar.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-slate-500">
                Diperbarui otomatis setiap 5 detik<span x-show="updatedAt" x-text="` · terakhir ${updatedAt}`"></span>.
                Skor sementara dihitung dari jawaban yang sudah dipilih peserta. Skor final dan peringkat per posisi
                (nilai, lalu waktu tercepat) keluar setelah waktu habis.
            </p>
        </div>
    </div>

    <script>
        function liveBoard(initial, url) {
            return {
                board: initial,
                remaining: 0,
                clock: '',
                updatedAt: '',
                start() {
                    this.tick();
                    setInterval(() => this.tick(), 1000);
                    setInterval(() => this.refresh(), 5000);
                },
                tick() {
                    this.remaining = this.board.ends_at ? Math.max(0, this.board.ends_at - Date.now()) : 0;
                    const minutes = Math.floor(this.remaining / 60000);
                    const seconds = Math.floor((this.remaining % 60000) / 1000);
                    this.clock = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                },
                async refresh() {
                    if (document.hidden) {
                        return;
                    }
                    try {
                        const response = await fetch(url, { headers: { Accept: 'application/json' } });
                        if (response.ok) {
                            this.board = await response.json();
                            this.updatedAt = new Date().toLocaleTimeString('id-ID');
                        }
                    } catch (error) {
                        // Network hiccup: keep the last board and retry on the next tick.
                    }
                },
            };
        }
    </script>
</x-app-layout>
