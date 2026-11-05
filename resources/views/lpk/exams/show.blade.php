<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">{{ $exam->title }}</h2>
            @unless ($isLocked)
                <div class="flex gap-2">
                    <a href="{{ route('lpk.exams.edit', $exam) }}"><x-secondary-button>Ubah</x-secondary-button></a>
                    @unless ($pendingPayment)
                        <form method="POST" action="{{ route('lpk.exams.destroy', $exam) }}" onsubmit="return confirm('Hapus sesi tes ini?')">
                            @csrf @method('DELETE')
                            <x-danger-button>Hapus</x-danger-button>
                        </form>
                    @endunless
                </div>
            @endunless
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />
            <x-input-error :messages="$errors->get('payment')" />

            <x-session-summary :exam="$exam" />

            {{-- Publish: pay the session fee --}}
            @if (! $exam->isPublished())
                @if ($pendingPayment?->isMidtrans())
                    @include('payments.partials.qris-waiter', ['payment' => $pendingPayment, 'caption' => 'Penerbitan sesi tes · terbit otomatis setelah dibayar'])
                @elseif ($pendingPayment)
                    @include('payments.partials.manual-waiter', ['payment' => $pendingPayment, 'caption' => 'Penerbitan sesi tes · terbit otomatis setelah diverifikasi'])
                @else
                    <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-blue-600 via-blue-800 to-blue-950 p-6 sm:p-8 text-white shadow-soft">
                        <x-batik.kawung class="text-white/[0.08]" size="40" />
                        <div class="relative flex flex-wrap items-end justify-between gap-6">
                            <div>
                                <div class="text-sm text-blue-100">Terbitkan sesi tes</div>
                                <div class="mt-1 text-5xl font-extrabold tracking-tight">Rp {{ number_format($fee, 0, ',', '.') }}</div>
                                <div class="mt-1 text-sm text-blue-100">Setelah terbit, semua pencari kerja aktif mendapat notifikasi dan bisa mendaftar.</div>
                            </div>
                            @if ($publishCheck->allowed() && $qrisAvailable)
                                <form method="POST" action="{{ route('lpk.exams.publish', $exam) }}" onsubmit="return confirm('Setelah terbit, isi sesi dan soal dikunci. Lanjutkan?')">
                                    @csrf
                                    <button type="submit" class="rounded-full bg-white px-7 py-3.5 text-base font-semibold text-blue-900 shadow-sm transition hover:bg-blue-50">Bayar & Terbitkan (QRIS)</button>
                                </form>
                            @endif
                        </div>
                        @if ($publishCheck->denied())
                            <p class="relative mt-4 rounded-2xl bg-white/10 p-3 text-sm">{{ $publishCheck->message() }}</p>
                        @elseif (! $qrisAvailable)
                            <p class="relative mt-4 rounded-2xl bg-white/10 p-3 text-sm">QRIS otomatis belum aktif. Bayar manual dan unggah bukti transfer di bawah.</p>
                        @endif
                    </div>
                    @if ($rejectedPayment)
                        <div class="rounded-2xl bg-red-50 p-4 text-sm text-red-700 ring-1 ring-red-200">
                            Bukti transfer {{ $rejectedPayment->created_at->translatedFormat('d M Y H:i') }} ditolak admin: {{ $rejectedPayment->note }}. Silakan kirim bukti baru.
                        </div>
                    @endif
                    @if ($publishCheck->allowed())
                        @include('payments.partials.manual-proof-form', [
                            'action' => route('lpk.exams.publish.manual', $exam),
                            'amount' => $fee,
                            'open' => ! $qrisAvailable,
                            'note' => 'Sesi terbit otomatis setelah pembayaran diverifikasi.',
                        ])
                    @endif
                @endif
            @endif

            {{-- Start the test in the room, follow it live --}}
            @if ($exam->isWaitingForStart())
                <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-amber-500 via-amber-600 to-orange-700 p-6 sm:p-8 text-white shadow-soft">
                    <x-batik.kawung class="text-white/[0.08]" size="40" />
                    <div class="relative flex flex-wrap items-end justify-between gap-6">
                        <div>
                            <div class="text-sm text-amber-100">Pendaftaran ditutup · {{ $participants->count() }} peserta siap tes</div>
                            <div class="mt-1 text-3xl font-extrabold tracking-tight">Mulai tes di ruangan</div>
                            <div class="mt-1 text-sm text-amber-50">Tekan saat semua peserta sudah di ruangan. Semua peserta mendapat waktu {{ $exam->duration_minutes }} menit yang sama.</div>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('sessions.live', $exam) }}" class="rounded-full bg-white/15 px-6 py-3.5 text-base font-semibold text-white ring-1 ring-white/40 transition hover:bg-white/25">Lihat Kehadiran</a>
                            <form method="POST" action="{{ route('lpk.exams.start', $exam) }}" onsubmit="return confirm('Mulai tes sekarang untuk semua peserta? Waktu {{ $exam->duration_minutes }} menit langsung berjalan.')">
                                @csrf
                                <button type="submit" class="rounded-full bg-white px-7 py-3.5 text-base font-semibold text-amber-800 shadow-sm transition hover:bg-amber-50">Mulai Tes Sekarang</button>
                            </form>
                        </div>
                    </div>
                </div>
            @elseif ($exam->isRunning())
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
                    <span>Tes sedang berlangsung sejak {{ $exam->started_at->format('H:i') }}, berakhir {{ $exam->ends_at->format('H:i') }} WIB.</span>
                    <a href="{{ route('sessions.live', $exam) }}"><x-primary-button type="button">Buka Live Score</x-primary-button></a>
                </div>
            @elseif ($exam->isOpenForRegistration())
                <div class="rounded-2xl bg-blue-50 p-4 text-sm text-blue-900 ring-1 ring-blue-200">
                    Tombol <strong>Mulai Tes</strong> muncul di sini pada jadwal {{ $exam->starts_at->translatedFormat('d M Y H:i') }} WIB, saat pendaftaran ditutup.
                </div>
            @endif

            {{-- Postpone --}}
            @if ($exam->isOpenForRegistration())
                <x-card title="Mundurkan Jadwal">
                    @if ($exam->min_participants && $participants->count() < $exam->min_participants)
                        <div class="mb-4 rounded-2xl bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-200">
                            Peserta siap tes baru {{ $participants->count() }} dari target {{ $exam->min_participants }}. Anda bisa memundurkan jadwal agar lebih banyak yang mendaftar.
                        </div>
                    @endif
                    <form method="POST" action="{{ route('lpk.exams.reschedule', $exam) }}" class="grid sm:grid-cols-2 gap-4"
                          onsubmit="return confirm('Mundurkan jadwal? Semua peserta akan diberi tahu dan boleh keluar sesi dengan token.')">
                        @csrf
                        <div>
                            <x-input-label for="starts_at" value="Tes dibuka (baru)" />
                            <x-text-input id="starts_at" type="datetime-local" name="starts_at" class="mt-1 block w-full" :value="old('starts_at')" required />
                            <x-input-error :messages="$errors->get('starts_at')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="ends_at" value="Tes ditutup (baru)" />
                            <x-text-input id="ends_at" type="datetime-local" name="ends_at" class="mt-1 block w-full" :value="old('ends_at')" required />
                            <x-input-error :messages="$errors->get('ends_at')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="reason" value="Alasan (dikirim ke peserta)" />
                            <x-textarea id="reason" name="reason" rows="2" class="mt-1 block w-full" required>{{ old('reason') }}</x-textarea>
                            <x-input-error :messages="$errors->get('reason')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2 flex flex-wrap items-center gap-3">
                            <x-primary-button>Mundurkan Jadwal</x-primary-button>
                            <span class="text-xs text-slate-500">Hanya bisa diundur, tidak bisa dimajukan. Peserta yang terdaftar sebelum perubahan boleh keluar dan mendapat 1 token.</span>
                        </div>
                    </form>
                </x-card>
            @endif

            {{-- Final ranking --}}
            @if ($exam->hasEnded())
                @php($sessionInterviews = $exam->interviews()->get()->keyBy('user_id'))
                @foreach ($rankingGroups as $group)
                    <x-card :title="'Peringkat Final (Lulus)'.($group['position'] ? ' · '.$group['label'] : '')">
                        <x-session-ranking :ranking="$group['ranking']" :interviews="$sessionInterviews->where('exam_position_id', $group['position']?->id)" :quota="$group['quota']" />
                    </x-card>
                @endforeach
            @endif

            <x-card title="Peserta">
                @if ($participants->isEmpty())
                    <p class="text-sm text-slate-500">{{ $exam->isPublished() ? 'Belum ada peserta yang mendaftar.' : 'Peserta bisa mendaftar setelah sesi diterbitkan.' }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs uppercase text-slate-500">
                                <tr><th class="py-2">Peserta</th><th>Posisi</th><th>Terdaftar</th><th>Status</th><th>Nilai</th><th>Benar</th><th>Evaluasi</th></tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach ($participants as $attempt)
                                    <tr>
                                        <td class="py-2"><a class="text-blue-700 underline" href="{{ route('candidates.show', $attempt->user) }}">{{ $attempt->user->name }}</a></td>
                                        <td>{{ $attempt->position?->position ?? '-' }}</td>
                                        <td class="text-slate-500">{{ $attempt->registered_at->format('d M H:i') }}</td>
                                        <td><x-badge :color="match (true) { $attempt->passed === true => 'green', $attempt->passed === false => 'red', $attempt->isAbsent() => 'gray', default => 'yellow' }">{{ $attempt->statusLabel() }}</x-badge></td>
                                        <td>{{ $attempt->score ?? '-' }}</td>
                                        <td>{{ $attempt->isSubmitted() ? $attempt->correct_count.'/'.$attempt->total_questions : '-' }}</td>
                                        <td>{{ $attempt->evaluation ? 'Sudah' : ($attempt->isSubmitted() ? 'Belum' : '-') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @php($withdrawn = $exam->attempts->filter->isWithdrawn()->count())
                @if ($withdrawn)
                    <p class="mt-3 text-xs text-slate-500">{{ $withdrawn }} peserta keluar sesi setelah jadwal dimundurkan (mendapat token).</p>
                @endif
            </x-card>

            {{-- Questions --}}
            <div class="grid sm:grid-cols-3 gap-4">
                <div class="panel p-5 sm:col-span-3">
                    <div class="text-sm text-slate-500">Jumlah soal</div>
                    <div class="mt-1 text-4xl font-semibold tracking-tight text-slate-900">{{ $exam->questions->count() }}</div>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($questionsPerCategory as $name => $count)
                            <x-badge color="blue">{{ $name }}: {{ $count }}</x-badge>
                        @endforeach
                    </div>
                </div>
            </div>

            @if ($isLocked)
                <div class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">Sesi sudah terbit sehingga soal dikunci. Semua peserta dinilai dengan soal yang sama (urutan soal diacak per peserta).</div>
            @else
                <x-card title="Ambil Soal dari Bank Soal">
                    @if ($categories->isEmpty())
                        <p class="text-sm text-slate-500">Belum ada kategori. <a class="font-semibold text-blue-700 hover:underline" href="{{ route('lpk.categories.index') }}">Buat bank soal</a>.</p>
                    @else
                        <form method="POST" action="{{ route('lpk.exams.bank.store', $exam) }}" class="flex flex-wrap items-end gap-3">
                            @csrf
                            <div>
                                <x-input-label for="question_category_id" value="Kategori" />
                                <x-select id="question_category_id" name="question_category_id" class="mt-1 min-w-64" required
                                    :options="$categories->mapWithKeys(fn ($category) => [$category->id => $category->name.' ('.$category->available_count.' soal tersedia)'])"
                                    :selected="old('question_category_id')" />
                            </div>
                            <div>
                                <x-input-label for="count" value="Jumlah soal (acak)" />
                                <x-text-input id="count" type="number" name="count" min="1" class="mt-1 w-36" :value="old('count', 50)" required />
                            </div>
                            <x-primary-button>Ambil Acak</x-primary-button>
                        </form>
                        <x-input-error :messages="$errors->bank->all()" class="mt-2" />
                        <p class="mt-3 text-xs text-slate-500">Bisa diulang untuk kategori lain, misalnya 30 Psikotes + 20 Pengetahuan Umum. Soal yang sudah ada di ujian tidak akan terambil dua kali.</p>
                    @endif
                </x-card>
            @endif

            <x-card title="Soal">
                @if (! $isLocked && $exam->questions->isNotEmpty())
                    <x-slot name="actions">
                        <form method="POST" action="{{ route('lpk.exams.questions.clear', $exam) }}" onsubmit="return confirm('Hapus semua {{ $exam->questions->count() }} soal dari ujian ini?')">
                            @csrf @method('DELETE')
                            <button class="text-sm font-semibold text-red-600 hover:underline">Kosongkan soal</button>
                        </form>
                    </x-slot>
                @endif

                @forelse ($exam->questions as $question)
                    <div class="py-3 {{ $loop->first ? 'pt-0' : 'border-t border-slate-100' }} text-sm">
                        <div class="flex justify-between gap-3">
                            <div class="font-medium">
                                {{ $loop->iteration }}. {{ $question->question }}
                                @if ($question->category) <x-badge class="ms-1">{{ $question->category->name }}</x-badge> @endif
                            </div>
                            @unless ($isLocked)
                                <form method="POST" action="{{ route('lpk.exams.questions.destroy', [$exam, $question]) }}">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 text-xs underline">Hapus</button>
                                </form>
                            @endunless
                        </div>
                        <ul class="mt-1 ms-5 list-[upper-alpha]">
                            @foreach ($question->options as $option)
                                <li class="{{ $option->is_correct ? 'text-green-700 font-medium' : 'text-slate-700' }}">
                                    {{ $option->text }} @if ($option->is_correct) ✓ @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada soal.</p>
                @endforelse

                @unless ($isLocked)
                    <details class="mt-6 border-t border-slate-100 pt-4" @if ($errors->has('question') || $errors->has('options')) open @endif>
                        <summary class="cursor-pointer text-sm font-semibold text-slate-700">Tambah soal manual (khusus sesi ini)</summary>
                        <div class="mt-3">
                            @include('lpk.partials.question-form', [
                                'action' => route('lpk.exams.questions.store', $exam),
                                'submitLabel' => 'Tambah Soal',
                            ])
                        </div>
                    </details>
                @endunless
            </x-card>
        </div>
    </div>
</x-app-layout>
