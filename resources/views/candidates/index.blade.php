<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Daftar Kandidat</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <form method="GET" class="panel p-4 grid sm:grid-cols-5 gap-3 items-end">
                <div class="sm:col-span-2">
                    <x-input-label for="search" value="Cari nama / email" />
                    <x-text-input id="search" name="search" class="mt-1 block w-full" :value="$filters['search'] ?? ''" />
                </div>
                <div>
                    <x-input-label for="education_level" value="Pendidikan" />
                    <x-select id="education_level" name="education_level" class="mt-1 block w-full" placeholder="Semua"
                        :options="collect($educationLevels)->mapWithKeys(fn ($level) => [$level->value => $level->label()])"
                        :selected="$filters['education_level'] ?? ''" />
                </div>
                <div>
                    <x-input-label for="skill" value="Skill" />
                    <x-text-input id="skill" name="skill" class="mt-1 block w-full" :value="$filters['skill'] ?? ''" />
                </div>
                <div>
                    <x-input-label for="theory" value="Tes Teori" />
                    <x-select id="theory" name="theory" class="mt-1 block w-full" placeholder="Semua"
                        :options="['passed' => 'Lulus', 'failed' => 'Tidak lulus', 'none' => 'Belum tes']"
                        :selected="$filters['theory'] ?? ''" />
                </div>
                <div class="sm:col-span-5 flex gap-2">
                    <x-primary-button>Filter</x-primary-button>
                    <a href="{{ route('candidates.index') }}"><x-secondary-button>Reset</x-secondary-button></a>
                </div>
            </form>

            <div>
                <div class="panel overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Kandidat</th>
                                <th class="px-4 py-3">Pendidikan</th>
                                <th class="px-4 py-3">Skill</th>
                                <th class="px-4 py-3">Domisili</th>
                                <th class="px-4 py-3">Tes Teori</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($candidates as $candidate)
                                @php($profile = $candidate->candidateProfile)
                                @php($bestAttempt = $candidate->examAttempts->sortByDesc('score')->first())
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <x-avatar :user="$candidate" />
                                            <div>
                                                <div class="font-medium text-slate-900">{{ $candidate->name }}</div>
                                                <div class="text-slate-500">{{ $profile?->gender?->label() }}{{ $profile?->birth_date ? ', '.$profile->birth_date->age.' th' : '' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">{{ $profile?->education_level?->label() }} {{ $profile?->education_major }}</td>
                                    <td class="px-4 py-3">{{ $candidate->skills->pluck('name')->join(', ') ?: '-' }}</td>
                                    <td class="px-4 py-3">{{ $profile?->city }}</td>
                                    <td class="px-4 py-3">
                                        @if ($bestAttempt)
                                            <x-badge :color="$candidate->examAttempts->contains('passed', true) ? 'green' : 'red'">
                                                {{ $candidate->examAttempts->contains('passed', true) ? 'Lulus' : 'Tidak lulus' }} · {{ $bestAttempt->score }}
                                            </x-badge>
                                        @else
                                            <x-badge>Belum tes</x-badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a class="text-blue-700 underline" href="{{ route('candidates.show', $candidate) }}">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Tidak ada kandidat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $candidates->links() }}
        </div>
    </div>
</x-app-layout>
