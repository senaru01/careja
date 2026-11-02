<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">{{ $candidate->name }}</h2>
            <x-badge color="indigo">{{ $candidate->candidateStage() }}</x-badge>
        </div>
    </x-slot>

    @php($profile = $candidate->candidateProfile)
    @php($viewer = auth()->user())

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-3"><x-flash /></div>

            {{-- Left: profile --}}
            <div class="space-y-6">
                @php($membership = $candidate->activeMembership())
                <x-profile-hero :user="$candidate"
                    :subtitle="$profile?->education_level ? $profile->education_level->label().' '.$profile->education_major : 'Pencari Kerja'"
                    :chip="$membership ? 'Aktif s.d. '.$membership->expires_at->format('d M Y') : 'Tidak aktif'" />

                <x-card title="Data Diri">
                    <dl class="text-sm space-y-2">
                        @foreach ([
                            'Email' => $candidate->email,
                            'HP' => $profile?->phone,
                            'Jenis kelamin' => $profile?->gender?->label(),
                            'Lahir' => $profile ? $profile->birth_place.', '.$profile->birth_date?->format('d M Y').' ('.$profile->birth_date?->age.' th)' : null,
                            'Domisili' => $profile ? $profile->city.', '.$profile->province : null,
                            'Pendidikan' => $profile ? $profile->education_level?->label().' '.$profile->education_major.' — '.$profile->education_institution.' ('.$profile->graduation_year.')' : null,
                        ] as $label => $value)
                            <div><dt class="text-slate-500">{{ $label }}</dt><dd class="text-slate-900">{{ $value ?: '-' }}</dd></div>
                        @endforeach
                    </dl>
                    @if ($profile?->summary)
                        <p class="mt-4 text-sm text-slate-700 whitespace-pre-line">{{ $profile->summary }}</p>
                    @endif
                </x-card>

                <x-card title="Skill">
                    <div class="flex flex-wrap gap-2">
                        @forelse ($candidate->skills as $skill)
                            <x-badge color="indigo">{{ $skill->name }} · {{ $skill->level->label() }}</x-badge>
                        @empty
                            <span class="text-sm text-slate-500">-</span>
                        @endforelse
                    </div>
                </x-card>

                <x-card title="Dokumen">
                    <ul class="text-sm space-y-1">
                        @forelse ($candidate->documents as $document)
                            <li><x-badge color="blue">{{ $document->type->label() }}</x-badge>
                                <a class="text-blue-700 underline" target="_blank" href="{{ route('documents.show', $document) }}">{{ $document->original_name }}</a></li>
                        @empty
                            <li class="text-slate-500">-</li>
                        @endforelse
                    </ul>
                </x-card>
            </div>

            {{-- Right: experience & stages --}}
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Pengalaman Kerja">
                    @forelse ($candidate->workExperiences as $experience)
                        <div class="text-sm py-2 {{ $loop->first ? '' : 'border-t' }}">
                            <div class="font-medium">{{ $experience->position }} — {{ $experience->company_name }}</div>
                            <div class="text-slate-500">{{ $experience->start_date->format('M Y') }} – {{ $experience->end_date?->format('M Y') ?? 'Sekarang' }}</div>
                            @if ($experience->description) <div class="text-slate-600">{{ $experience->description }}</div> @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada pengalaman kerja.</p>
                    @endforelse
                </x-card>

                <x-card title="Tahap 1 — Sesi Tes Teori (LPK)">
                    @forelse ($candidate->examAttempts as $attempt)
                        <div class="py-4 {{ $loop->first ? '' : 'border-t' }}">
                            <x-attempt-result :attempt="$attempt" />
                            <div class="text-xs text-slate-500 mt-1">Sesi "{{ $attempt->exam->title }}" oleh {{ $attempt->exam->organization->name }}</div>
                            <x-category-breakdown :attempt="$attempt" class="mt-3" />

                            @if ($attempt->evaluation)
                                <div class="mt-3 p-3 rounded bg-blue-50 text-sm">
                                    <div class="font-medium text-blue-900">Evaluasi LPK
                                        <span class="font-normal text-xs text-blue-700">· {{ $attempt->evaluator?->name }}, {{ $attempt->evaluated_at?->format('d M Y H:i') }}</span>
                                    </div>
                                    <p class="text-blue-900 whitespace-pre-line">{{ $attempt->evaluation }}</p>
                                </div>
                            @endif

                            @can('evaluate', $attempt)
                                @php($bag = 'evaluation'.$attempt->id)
                                <form method="POST" action="{{ route('lpk.attempts.evaluate', $attempt) }}" class="mt-3 space-y-2">
                                    @csrf @method('PUT')
                                    <x-textarea name="evaluation" class="block w-full text-sm" rows="3" placeholder="Tulis evaluasi: kekuatan, kelemahan, dan saran skill up untuk kandidat">{{ old('evaluation', $attempt->evaluation) }}</x-textarea>
                                    <x-input-error :messages="$errors->{$bag}->get('evaluation')" />
                                    <x-secondary-button type="submit">{{ $attempt->evaluation ? 'Perbarui Evaluasi' : 'Simpan Evaluasi' }}</x-secondary-button>
                                </form>
                            @endcan
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Kandidat belum mengikuti sesi tes.</p>
                    @endforelse
                </x-card>

                <x-card title="Tahap 2 — Wawancara (Perusahaan)">
                    @foreach ($candidate->interviews as $interview)
                        <div class="py-4 {{ $loop->first ? '' : 'border-t' }} text-sm space-y-1">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="font-medium text-slate-900">{{ $interview->organization->name }}</span>
                                <x-badge :color="$interview->outcomeColor()">{{ $interview->outcomeLabel() }}</x-badge>
                            </div>
                            <div class="text-slate-600">{{ $interview->scheduled_at->format('d M Y H:i') }} · {{ $interview->location }}</div>
                            @if ($interview->exam)
                                <div class="text-xs text-slate-500">Dari sesi "{{ $interview->exam->title }}", peringkat #{{ $interview->exam_rank }}</div>
                            @endif
                            @if ($interview->skip_reason)
                                <div class="text-xs text-slate-500">{{ $interview->skip_reason }}</div>
                            @endif

                            @if ($interview->isEvaluated())
                                <div class="mt-2 p-3 rounded bg-blue-50">
                                    <div class="font-medium text-blue-900">Nilai {{ $interview->score }} / 100
                                        <span class="font-normal text-xs text-blue-700">· {{ $interview->evaluator?->name }}, {{ $interview->evaluated_at?->format('d M Y H:i') }}</span>
                                    </div>
                                    <p class="text-blue-900 whitespace-pre-line">{{ $interview->evaluation }}</p>
                                </div>
                            @elseif ($viewer->can('evaluate', $interview))
                                <form method="POST" action="{{ route('company.interviews.update', $interview) }}" class="mt-3 grid sm:grid-cols-3 gap-3">
                                    @csrf @method('PUT')
                                    <x-select name="status" placeholder="Keputusan" required
                                        :options="['passed' => 'Diterima', 'failed' => 'Tidak Diterima']" :selected="old('status')" />
                                    <x-text-input type="number" name="score" min="0" max="100" placeholder="Nilai 0-100" :value="old('score')" required />
                                    <x-primary-button>Simpan Hasil</x-primary-button>
                                    <x-textarea name="evaluation" class="sm:col-span-3 text-sm" rows="3" placeholder="Evaluasi wawancara (bahan skill up kandidat)" required>{{ old('evaluation') }}</x-textarea>
                                    <x-input-error class="sm:col-span-3" :messages="array_merge($errors->get('status'), $errors->get('score'), $errors->get('evaluation'))" />
                                    <p class="sm:col-span-3 text-xs text-slate-500">Hasil yang sudah disimpan tidak dapat diubah.</p>
                                </form>
                            @endif
                        </div>
                    @endforeach

                    @if ($candidate->interviews->isEmpty())
                        <p class="text-sm text-slate-500">Perusahaan memilih kandidat dari peringkat sesi tes yang sudah selesai.</p>
                    @endif
                </x-card>

                @include('candidates.partials.medical')
            </div>
        </div>
    </div>
</x-app-layout>
