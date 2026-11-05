<x-app-layout>
    <x-slot name="header">
        <x-welcome-header :user="auth()->user()" />
    </x-slot>

    <div class="pt-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <div class="grid lg:grid-cols-3 gap-6">
                <x-profile-hero :user="auth()->user()" :subtitle="$organization->name" chip="Perusahaan" />

                <div class="lg:col-span-2 grid grid-cols-2 gap-4">
                    @foreach ($stats as $label => $value)
                        <div class="panel p-5">
                            <div class="text-sm text-slate-500">{{ $label }}</div>
                            <div class="mt-1 text-4xl font-semibold tracking-tight text-slate-900">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <x-card title="Rekrutmen Karyawan">
                <x-slot name="actions">
                    <div class="flex items-center gap-3">
                        <a class="text-sm font-semibold text-blue-700 hover:underline" href="{{ route('company.recruitments.index') }}">Semua permintaan</a>
                        <a href="{{ route('company.recruitments.create') }}"><x-primary-button type="button">Buat Permintaan</x-primary-button></a>
                    </div>
                </x-slot>
                @forelse ($recruitments as $recruitment)
                    <div class="py-3 {{ $loop->first ? 'pt-0' : 'border-t border-slate-100' }} flex flex-wrap items-center justify-between gap-3 text-sm">
                        <div>
                            <a class="font-semibold text-blue-700 hover:underline" href="{{ route('company.recruitments.show', $recruitment) }}">{{ $recruitment->title }}</a>
                            <div class="text-slate-500">{{ $recruitment->positions->map(fn ($position) => $position->position.' '.$position->quota.' org')->join(', ') }}</div>
                        </div>
                        <x-badge :color="$recruitment->statusColor()">{{ $recruitment->statusLabel() }}</x-badge>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada permintaan. Rilis kebutuhan karyawan Anda (posisi, jumlah, pendidikan, umur, pengalaman) dan LPK akan menindaklanjuti dengan sesi tes.</p>
                @endforelse
            </x-card>

            <div class="grid lg:grid-cols-2 gap-6">
                <x-card title="Sesi Tes Perusahaan Anda">
                    <x-slot name="actions">
                        <a class="text-sm font-semibold text-blue-700 hover:underline" href="{{ route('company.sessions.index') }}">Semua sesi</a>
                    </x-slot>
                    @forelse ($sessions as $session)
                        @php($phase = $session->phase())
                        <div class="py-3 {{ $loop->first ? 'pt-0' : 'border-t border-slate-100' }} flex flex-wrap items-center justify-between gap-3 text-sm">
                            <div>
                                <a class="font-semibold text-blue-700 hover:underline" href="{{ route('company.sessions.show', $session) }}">{{ $session->position }}</a>
                                <div class="text-slate-500">{{ $session->organization->name }} · {{ $session->starts_at->format('d M Y H:i') }} · butuh {{ $session->quota }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-badge :color="$phase->color()">{{ $phase->label() }}</x-badge>
                                <x-badge color="blue">{{ $session->participants_count }} siap tes</x-badge>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada sesi tes untuk perusahaan Anda. Buat permintaan rekrutmen, LPK akan membuat sesi sesuai kebutuhan Anda.</p>
                    @endforelse
                </x-card>

                <x-card title="Wawancara Mendatang">
                    <x-slot name="actions">
                        <a class="text-sm font-semibold text-blue-700 hover:underline" href="{{ route('company.interviews.index') }}">Semua wawancara</a>
                    </x-slot>

                    @forelse ($upcomingInterviews as $interview)
                        <div class="py-3 {{ $loop->first ? 'pt-0' : 'border-t border-slate-100' }} text-sm flex justify-between gap-3">
                            <a class="font-semibold text-blue-700 hover:underline" href="{{ route('candidates.show', $interview->user) }}">{{ $interview->user->name }}</a>
                            <span class="text-slate-500">{{ $interview->scheduled_at->format('d M Y H:i') }} · {{ $interview->location }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Tidak ada wawancara terjadwal.</p>
                    @endforelse
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
