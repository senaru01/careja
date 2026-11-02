<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">{{ $exam->position }}</h2>
    </x-slot>

    @php
        // Default pick: the best ranked candidates that are not invited yet, up to the open seats.
        $preselect = $ranking->reject(fn ($attempt) => $interviews->has($attempt->user_id))->take($openSeats)->pluck('user_id')->all();
    @endphp

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <x-session-summary :exam="$exam" />

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach ([
                    'Lulus tes' => $ranking->count(),
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
                <div class="rounded-2xl bg-blue-50 p-4 text-sm text-blue-900 ring-1 ring-blue-200">
                    Peringkat final dan pemilihan kandidat dibuka setelah sesi ditutup pada {{ $exam->ends_at->translatedFormat('d M Y H:i') }} WIB,
                    supaya semua peserta punya kesempatan yang sama.
                </div>
            @else
                <form method="POST" action="{{ route('company.interviews.store', $exam) }}" class="space-y-6">
                    @csrf
                    <x-card title="Peringkat Final">
                        <p class="mb-4 text-sm text-slate-600">
                            Peringkat teratas sudah dicentang sesuai sisa kuota. Jika Anda melewati peserta berperingkat lebih tinggi
                            (mis. keahliannya tidak sesuai), wajib menulis alasan — alasan ini terlihat oleh LPK dan peserta.
                        </p>
                        <x-session-ranking :ranking="$ranking" :interviews="$interviews" :quota="$exam->quota" :selectable="$canSelect && $openSeats > 0" :preselect="$preselect" />
                        <x-input-error :messages="$errors->get('user_ids')" class="mt-2" />
                    </x-card>

                    @if ($openSeats > 0 && $ranking->count() > $interviews->count())
                        <x-card title="Undang Wawancara">
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="scheduled_at" value="Jadwal wawancara" />
                                    <x-text-input id="scheduled_at" type="datetime-local" name="scheduled_at" class="mt-1 block w-full" :value="old('scheduled_at')" required />
                                    <x-input-error :messages="$errors->get('scheduled_at')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="location" value="Tempat / link meeting" />
                                    <x-text-input id="location" name="location" class="mt-1 block w-full" :value="old('location')" required />
                                    <x-input-error :messages="$errors->get('location')" class="mt-1" />
                                </div>
                                <div class="sm:col-span-2">
                                    <x-input-label for="notes" value="Catatan untuk kandidat (opsional)" />
                                    <x-textarea id="notes" name="notes" class="mt-1 block w-full" rows="2">{{ old('notes') }}</x-textarea>
                                </div>
                                <div class="sm:col-span-2">
                                    <x-input-label for="skip_reason" value="Alasan melewati peringkat (wajib jika ada yang dilewati)" />
                                    <x-textarea id="skip_reason" name="skip_reason" class="mt-1 block w-full" rows="2"
                                        placeholder="mis. Peringkat 2 keahlian mesin bubut, posisi ini membutuhkan juru las bersertifikat">{{ old('skip_reason') }}</x-textarea>
                                    <x-input-error :messages="$errors->get('skip_reason')" class="mt-1" />
                                </div>
                            </div>
                            <div class="mt-4"><x-primary-button>Undang Kandidat Terpilih</x-primary-button></div>
                        </x-card>
                    @elseif ($openSeats === 0)
                        <div class="rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200">
                            Kuota sudah terisi. Jika ada kandidat yang tidak lolos wawancara atau medical, kursinya terbuka lagi dan Anda bisa memilih pengganti dari peringkat ini.
                        </div>
                    @endif
                </form>

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
