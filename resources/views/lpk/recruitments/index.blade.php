<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Permintaan Rekrutmen Perusahaan</h2>
            <p class="text-sm text-slate-500">Kebutuhan karyawan yang dirilis perusahaan. Buat sesi tes dari permintaan, lalu lengkapi detail tesnya.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            @forelse ($requests as $recruitment)
                <x-card>
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-blue-700">{{ $recruitment->company->name }}</div>
                            <div class="mt-1 text-xl font-semibold tracking-tight text-slate-900">{{ $recruitment->title }}</div>
                            <div class="text-sm text-slate-500">
                                Dirilis {{ $recruitment->created_at->translatedFormat('d M Y') }} · {{ $recruitment->totalQuota() }} orang
                                @if ($recruitment->needed_by) · target mulai kerja {{ $recruitment->needed_by->translatedFormat('d M Y') }} @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-badge :color="$recruitment->statusColor()">{{ $recruitment->statusLabel() }}</x-badge>
                            @unless ($recruitment->isClosed())
                                <a href="{{ route('lpk.exams.create', ['permintaan' => $recruitment->id]) }}"><x-primary-button type="button">Buat Sesi Tes</x-primary-button></a>
                            @endunless
                        </div>
                    </div>

                    <x-position-table :positions="$recruitment->positions" class="mt-4" />

                    @if ($recruitment->description)
                        <p class="mt-3 text-sm text-slate-700 whitespace-pre-line">{{ $recruitment->description }}</p>
                    @endif

                    @if ($recruitment->exams->isNotEmpty())
                        <div class="mt-4 border-t border-slate-100 pt-3 text-sm">
                            <div class="font-semibold text-slate-900">Sesi tes dari permintaan ini</div>
                            @foreach ($recruitment->exams as $exam)
                                @php($phase = $exam->phase())
                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                    @if ($exam->organization_id === auth()->user()->organization_id)
                                        <a class="text-blue-700 hover:underline" href="{{ route('lpk.exams.show', $exam) }}">{{ $exam->title }}</a>
                                    @else
                                        <span>{{ $exam->title }}</span>
                                    @endif
                                    <span class="text-slate-500">· {{ $exam->starts_at?->translatedFormat('d M Y H:i') }}</span>
                                    <x-badge :color="$phase->color()">{{ $phase->label() }}</x-badge>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            @empty
                <div class="panel p-8 text-center text-sm text-slate-500">
                    Belum ada permintaan rekrutmen dari perusahaan. Anda tetap bisa
                    <a class="font-semibold text-blue-700 hover:underline" href="{{ route('lpk.exams.create') }}">membuat sesi tes secara mandiri</a>.
                </div>
            @endforelse

            {{ $requests->links() }}
        </div>
    </div>
</x-app-layout>
