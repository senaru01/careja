<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">{{ $exam->position }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <x-session-summary :exam="$exam" />

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach ([
                    'Lulus tes' => $rankingGroups->sum(fn ($group) => $group['ranking']->count()),
                    'Diundang' => $interviews->count(),
                    'Kursi tersisa' => $openSeats,
                    'Diterima final' => $hired.' / '.$exam->quota,
                ] as $label => $value)
                    <div class="panel p-5">
                        <div class="text-sm text-slate-500">{{ $label }}</div>
                        <div class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            @if (! $exam->hasEnded())
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-blue-50 p-4 text-sm text-blue-900 ring-1 ring-blue-200">
                    <span>
                        Peringkat final dan pemilihan kandidat dibuka setelah sesi ditutup{{ $exam->started_at ? ' pada '.$exam->ends_at->translatedFormat('d M Y H:i').' WIB' : ' (setelah LPK memulai tes dan waktunya habis)' }},
                        supaya semua peserta punya kesempatan yang sama.
                    </span>
                    @if ($exam->isRunning() || $exam->isWaitingForStart())
                        <a href="{{ route('sessions.live', $exam) }}"><x-primary-button type="button">Lihat Live Score</x-primary-button></a>
                    @endif
                </div>
            @else
                @unless ($interviewAccessPaid)
                    @if ($pendingInterviewPayment?->isMidtrans())
                        @include('payments.partials.qris-waiter', ['payment' => $pendingInterviewPayment, 'caption' => 'Pengambilan kandidat wawancara · terbuka otomatis setelah dibayar'])
                    @elseif ($pendingInterviewPayment)
                        @include('payments.partials.manual-waiter', ['payment' => $pendingInterviewPayment, 'caption' => 'Pengambilan kandidat wawancara · terbuka otomatis setelah diverifikasi'])
                    @else
                        <x-card title="Pengambilan Kandidat Wawancara">
                            <p class="text-sm text-slate-600">
                                Untuk mengundang kandidat dari peringkat sesi ini, bayar biaya pengambilan kandidat
                                <strong>Rp {{ number_format($interviewFee, 0, ',', '.') }}</strong> sekali per sesi tes (bebas mengundang sesuai kuota, termasuk pengganti).
                            </p>
                            @if ($qrisAvailable)
                                <form method="POST" action="{{ route('company.sessions.payment.store', $exam) }}" class="mt-4">
                                    @csrf
                                    <x-primary-button>Bayar via QRIS</x-primary-button>
                                </form>
                            @else
                                <p class="mt-4 text-sm text-amber-700">QRIS otomatis belum aktif. Bayar manual dan unggah bukti transfer di bawah.</p>
                            @endif
                            @if ($rejectedInterviewPayment)
                                <div class="mt-4 rounded-2xl bg-red-50 p-3 text-sm text-red-700 ring-1 ring-red-200">
                                    Bukti transfer {{ $rejectedInterviewPayment->created_at->translatedFormat('d M Y H:i') }} ditolak admin: {{ $rejectedInterviewPayment->note }}. Silakan kirim bukti baru.
                                </div>
                            @endif
                        </x-card>
                        @include('payments.partials.manual-proof-form', [
                            'action' => route('company.sessions.payment.manual', $exam),
                            'amount' => $interviewFee,
                            'open' => ! $qrisAvailable,
                            'note' => 'Undangan wawancara terbuka setelah pembayaran diverifikasi.',
                        ])
                    @endif
                @endunless

                @foreach ($rankingGroups as $group)
                    @php
                        $groupKey = $group['position']?->id ?? 'all';
                        $groupInterviews = $interviews->where('exam_position_id', $group['position']?->id);
                        $groupOpenSeats = $exam->openSeats($group['position']);
                        $isActiveForm = (string) old('exam_position_id', '') === (string) ($group['position']?->id ?? '');
                        // Default pick: the best ranked candidates that are not invited yet, up to the open seats.
                        $preselect = $group['ranking']->reject(fn ($attempt) => $interviews->has($attempt->user_id))->take($groupOpenSeats)->pluck('user_id')->all();
                    @endphp
                    <form method="POST" action="{{ route('company.interviews.store', $exam) }}" class="space-y-6">
                        @csrf
                        @if ($group['position'])
                            <input type="hidden" name="exam_position_id" value="{{ $group['position']->id }}">
                        @endif
                        <x-card :title="$group['position'] ? 'Peringkat Final · '.$group['label'] : 'Peringkat Final'">
                            <x-slot name="actions">
                                <span class="text-sm text-slate-500">Kuota {{ $group['quota'] }} · sisa kursi <strong class="text-slate-900">{{ $groupOpenSeats }}</strong></span>
                            </x-slot>
                            <p class="mb-4 text-sm text-slate-600">
                                Peringkat teratas sudah dicentang sesuai sisa kuota. Jika Anda melewati peserta berperingkat lebih tinggi
                                (mis. keahliannya tidak sesuai), wajib menulis alasan. Alasan ini terlihat oleh LPK dan peserta.
                            </p>
                            <x-session-ranking :ranking="$group['ranking']" :interviews="$groupInterviews" :quota="$group['quota']" :selectable="$canSelect && $groupOpenSeats > 0" :preselect="$isActiveForm && old('user_ids') ? [] : $preselect" />
                            @if ($isActiveForm)
                                <x-input-error :messages="$errors->get('user_ids')" class="mt-2" />
                                <x-input-error :messages="$errors->get('exam_position_id')" class="mt-2" />
                            @endif
                        </x-card>

                        @if ($canSelect && $groupOpenSeats > 0 && $group['ranking']->count() > $groupInterviews->count())
                            <x-card :title="'Undang Wawancara'.($group['position'] ? ' · '.$group['label'] : '')">
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="scheduled_at_{{ $groupKey }}" value="Jadwal wawancara" />
                                        <x-text-input id="scheduled_at_{{ $groupKey }}" type="datetime-local" name="scheduled_at" class="mt-1 block w-full" :value="$isActiveForm ? old('scheduled_at') : ''" required />
                                        @if ($isActiveForm) <x-input-error :messages="$errors->get('scheduled_at')" class="mt-1" /> @endif
                                    </div>
                                    <div>
                                        <x-input-label for="location_{{ $groupKey }}" value="Tempat / link meeting" />
                                        <x-text-input id="location_{{ $groupKey }}" name="location" class="mt-1 block w-full" :value="$isActiveForm ? old('location') : ''" required />
                                        @if ($isActiveForm) <x-input-error :messages="$errors->get('location')" class="mt-1" /> @endif
                                    </div>
                                    <div class="sm:col-span-2">
                                        <x-input-label for="notes_{{ $groupKey }}" value="Catatan untuk kandidat (opsional)" />
                                        <x-textarea id="notes_{{ $groupKey }}" name="notes" class="mt-1 block w-full" rows="2">{{ $isActiveForm ? old('notes') : '' }}</x-textarea>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <x-input-label for="skip_reason_{{ $groupKey }}" value="Alasan melewati peringkat (wajib jika ada yang dilewati)" />
                                        <x-textarea id="skip_reason_{{ $groupKey }}" name="skip_reason" class="mt-1 block w-full" rows="2"
                                            placeholder="mis. Peringkat 2 keahlian mesin bubut, posisi ini membutuhkan juru las bersertifikat">{{ $isActiveForm ? old('skip_reason') : '' }}</x-textarea>
                                        @if ($isActiveForm) <x-input-error :messages="$errors->get('skip_reason')" class="mt-1" /> @endif
                                    </div>
                                </div>
                                <div class="mt-4"><x-primary-button>Undang Kandidat Terpilih</x-primary-button></div>
                            </x-card>
                        @elseif ($groupOpenSeats === 0)
                            <div class="rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200">
                                Kuota {{ $group['position'] ? 'posisi '.$group['label'] : '' }} sudah terisi. Jika ada kandidat yang tidak lolos wawancara atau medical, kursinya terbuka lagi dan Anda bisa memilih pengganti dari peringkat ini.
                            </div>
                        @endif
                    </form>
                @endforeach

                @if ($interviews->isNotEmpty())
                    <x-card title="Kandidat Terpilih">
                        <x-slot name="actions">
                            <a class="text-sm font-semibold text-blue-700 hover:underline" href="{{ route('company.interviews.index') }}">Input hasil wawancara & medical</a>
                        </x-slot>
                        <div class="divide-y divide-slate-100 text-sm">
                            @foreach ($interviews->sortBy('exam_rank') as $interview)
                                <div class="py-3 flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <a class="font-semibold text-blue-700 hover:underline" href="{{ route('candidates.show', $interview->user) }}">#{{ $interview->exam_rank }} {{ $interview->user->name }}</a>
                                        @if ($interview->position) <x-badge color="blue" class="ms-1">{{ $interview->position->position }}</x-badge> @endif
                                        <div class="text-slate-500">{{ $interview->scheduled_at->format('d M Y H:i') }} · {{ $interview->location }}</div>
                                    </div>
                                    <x-badge :color="$interview->outcomeColor()">{{ $interview->outcomeLabel() }}</x-badge>
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
