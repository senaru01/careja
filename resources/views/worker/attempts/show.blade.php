<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Tes Teori: {{ $attempt->exam->title }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            @if ($attempt->isSubmitted())
                {{-- Result --}}
                <x-card title="Hasil Tes">
                    <div class="text-center space-y-2">
                        <div class="text-5xl font-bold {{ $attempt->passed ? 'text-green-600' : 'text-red-600' }}">{{ $attempt->score }}</div>
                        <x-badge :color="$attempt->passed ? 'green' : 'red'">{{ $attempt->statusLabel() }}</x-badge>
                        <p class="text-sm text-slate-600">
                            {{ $attempt->correct_count }} dari {{ $attempt->total_questions }} jawaban benar. Nilai lulus minimal {{ $attempt->exam->passing_score }}.
                        </p>
                        <p class="text-sm text-slate-600">
                            Peringkat final keluar setelah sesi ditutup.
                            <a class="font-semibold text-blue-700 hover:underline" href="{{ route('worker.sessions.show', $attempt->exam) }}">Lihat peringkat sesi</a>
                        </p>
                        <p class="text-xs text-slate-500">Nilai dihitung otomatis oleh sistem dan dapat dilihat oleh LPK dan perusahaan.</p>
                    </div>

                    <x-category-breakdown :attempt="$attempt" class="mt-6" />

                    @if ($attempt->evaluation)
                        <div class="mt-6 p-4 rounded bg-blue-50 text-sm">
                            <div class="font-medium text-blue-900">Evaluasi dari {{ $attempt->exam->organization->name }}</div>
                            <p class="text-blue-900 whitespace-pre-line">{{ $attempt->evaluation }}</p>
                        </div>
                    @endif

                    <div class="mt-6 text-center">
                        <a href="{{ route('dashboard') }}" class="text-blue-700 underline text-sm">Kembali ke dashboard</a>
                    </div>
                </x-card>
            @elseif (! $attempt->isStarted())
                {{-- Intro --}}
                <x-input-error :messages="$errors->get('attempt')" />
                <x-card title="Sebelum Mulai">
                    <div class="space-y-2 text-sm text-slate-700">
                        @if ($attempt->exam->description)
                            <p>{{ $attempt->exam->description }}</p>
                        @endif
                        <ul class="list-disc ms-5">
                            <li>Penyelenggara: {{ $attempt->exam->organization->name }}</li>
                            <li>Jadwal: <strong>{{ $attempt->exam->starts_at?->translatedFormat('d M Y H:i') }} – {{ $attempt->exam->ends_at?->translatedFormat('d M Y H:i') }} WIB</strong>. Tes harus selesai sebelum sesi ditutup.</li>
                            <li>Durasi: <strong>{{ $attempt->exam->duration_minutes }} menit</strong>, waktu berjalan sejak tombol Mulai ditekan.</li>
                            <li>Nilai lulus minimal: <strong>{{ $attempt->exam->passing_score }}</strong>.</li>
                            <li>Jawaban otomatis terkirim saat waktu habis. Jawaban yang dikirim setelah waktu habis tidak dinilai.</li>
                            <li>Tes hanya bisa dikerjakan satu kali.</li>
                        </ul>
                    </div>
                    @if ($attempt->canStart())
                        <form method="POST" action="{{ route('worker.attempts.start', $attempt) }}" class="mt-6">
                            @csrf
                            <x-primary-button onclick="return confirm('Mulai tes sekarang? Waktu akan langsung berjalan.')">Mulai Tes</x-primary-button>
                        </form>
                    @elseif ($attempt->exam->isOpenForRegistration())
                        <p class="mt-6 text-sm font-semibold text-blue-800">Tes dibuka {{ $attempt->exam->starts_at->diffForHumans() }}.</p>
                    @else
                        <p class="mt-6 text-sm font-semibold text-red-700">Sesi tes sudah ditutup.</p>
                    @endif
                </x-card>
            @else
                {{-- Running exam --}}
                <div x-data="examTimer({{ $attempt->deadline()->timestamp * 1000 }})" x-init="start()"
                     class="sticky top-0 z-10 panel p-4 flex justify-between items-center">
                    <span class="text-sm text-slate-600">Sisa waktu</span>
                    <span class="text-xl font-mono font-semibold" :class="remaining < 60000 ? 'text-red-600' : 'text-slate-900'" x-text="label"></span>
                </div>

                <form id="exam-form" method="POST" action="{{ route('worker.attempts.submit', $attempt) }}" class="space-y-4">
                    @csrf
                    {{-- Order is shuffled per candidate (stable across refreshes); grading uses ids, so order does not matter. --}}
                    @foreach ($attempt->exam->questions->sortBy(fn ($question) => crc32($attempt->id.'-q-'.$question->id)) as $question)
                        <x-card>
                            <div class="font-medium text-slate-900 mb-3">{{ $loop->iteration }}. {{ $question->question }}</div>
                            <div class="space-y-2">
                                @foreach ($question->options->sortBy(fn ($option) => crc32($attempt->id.'-o-'.$option->id)) as $option)
                                    <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:bg-blue-50/50 cursor-pointer">
                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}" class="text-blue-700">
                                        <span class="text-sm">{{ $option->text }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </x-card>
                    @endforeach

                    <x-primary-button onclick="return confirm('Kirim jawaban sekarang? Jawaban tidak dapat diubah lagi.')">Kirim Jawaban</x-primary-button>
                </form>

                <script>
                    function examTimer(deadline) {
                        return {
                            remaining: deadline - Date.now(),
                            label: '',
                            start() {
                                const tick = () => {
                                    this.remaining = Math.max(0, deadline - Date.now());
                                    const minutes = Math.floor(this.remaining / 60000);
                                    const seconds = Math.floor((this.remaining % 60000) / 1000);
                                    this.label = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

                                    if (this.remaining === 0) {
                                        clearInterval(timer);
                                        document.getElementById('exam-form').submit();
                                    }
                                };
                                const timer = setInterval(tick, 1000);
                                tick();
                            },
                        };
                    }
                </script>
            @endif
        </div>
    </div>
</x-app-layout>
