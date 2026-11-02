{{-- Stage 3: final medical checkup for candidates that passed the interview. Expects $candidate and $viewer. --}}
<x-card title="Tahap 3 — Medical Checkup (Penilaian Akhir)">
    @forelse ($candidate->interviews->where('status', \App\Enums\InterviewStatus::Passed) as $interview)
        <div class="py-4 {{ $loop->first ? 'pt-0' : 'border-t' }} text-sm space-y-2">
            <div class="flex flex-wrap items-center gap-3">
                <span class="font-medium text-slate-900">{{ $interview->organization->name }}</span>
                <x-badge :color="$interview->outcomeColor()">{{ $interview->outcomeLabel() }}</x-badge>
            </div>

            @if ($interview->medical_status)
                <div class="rounded-2xl p-3 {{ $interview->medical_status->value === 'fit' ? 'bg-emerald-50 text-emerald-900' : 'bg-red-50 text-red-900' }}">
                    <div class="font-medium">{{ $interview->medical_status->label() }}
                        <span class="font-normal text-xs">· {{ $interview->medicalChecker?->name }}, {{ $interview->medical_checked_at?->format('d M Y H:i') }}</span>
                    </div>
                    <p class="whitespace-pre-line">{{ $interview->medical_notes }}</p>
                </div>
            @elseif ($viewer->can('recordMedical', $interview))
                <form method="POST" action="{{ route('company.interviews.medical', $interview) }}" class="grid sm:grid-cols-3 gap-3">
                    @csrf @method('PUT')
                    <x-select name="medical_status" placeholder="Hasil medical" required
                        :options="['fit' => 'Sehat (Fit)', 'unfit' => 'Tidak Sehat (Unfit)']" :selected="old('medical_status')" />
                    <div class="sm:col-span-2"><x-primary-button>Simpan Hasil Medical</x-primary-button></div>
                    <x-textarea name="medical_notes" class="sm:col-span-3 text-sm" rows="3" placeholder="Ringkasan hasil medical checkup (klinik, tanggal, temuan)" required>{{ old('medical_notes') }}</x-textarea>
                    <x-input-error class="sm:col-span-3" :messages="array_merge($errors->get('medical_status'), $errors->get('medical_notes'))" />
                    <p class="sm:col-span-3 text-xs text-slate-500">Hasil tidak dapat diubah. Jika tidak sehat, kursi kuota terbuka lagi dan Anda bisa memilih pengganti dari peringkat sesi tes.</p>
                </form>
            @else
                <p class="text-slate-500">Menunggu hasil medical checkup dari perusahaan.</p>
            @endif
        </div>
    @empty
        <p class="text-sm text-slate-500">Medical checkup dilakukan setelah kandidat lulus wawancara.</p>
    @endforelse
</x-card>
