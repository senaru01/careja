<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">Ubah Kategori</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <form method="POST" action="{{ route('lpk.categories.update', $category) }}" class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <x-input-label for="name" value="Nama kategori" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $category->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="description" value="Deskripsi (opsional)" />
                        <x-textarea id="description" name="description" class="mt-1 block w-full" rows="3">{{ old('description', $category->description) }}</x-textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>
                    <div class="flex gap-3">
                        <x-primary-button>Simpan</x-primary-button>
                        <a href="{{ route('lpk.categories.show', $category) }}"><x-secondary-button>Batal</x-secondary-button></a>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
