<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">LPK & Perusahaan</h2>
            <a href="{{ route('admin.organizations.create') }}"><x-primary-button type="button">Tambah</x-primary-button></a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <div class="grid md:grid-cols-2 gap-6">
                @forelse ($organizations as $organization)
                    <x-card :title="$organization->name">
                        <x-slot name="actions"><x-badge color="indigo">{{ $organization->type->label() }}</x-badge></x-slot>
                        <dl class="text-sm space-y-1">
                            <div>{{ $organization->address ?: '-' }}</div>
                            <div class="text-slate-500">{{ $organization->email }} {{ $organization->phone ? '· '.$organization->phone : '' }}</div>
                        </dl>
                        <div class="mt-4 text-sm">
                            <div class="font-medium text-slate-700">Akun login</div>
                            <ul class="text-slate-600">
                                @foreach ($organization->users as $account)
                                    <li>{{ $account->name }} — {{ $account->email }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </x-card>
                @empty
                    <p class="text-sm text-slate-500">Belum ada LPK atau perusahaan.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
