@if (session('status'))
    <div {{ $attributes->merge(['class' => 'flex items-center gap-2 rounded-2xl bg-emerald-50/90 px-5 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200']) }}>
        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
        {{ session('status') }}
    </div>
@endif

@foreach (['user', 'payment'] as $key)
    @error($key)
        <div {{ $attributes->merge(['class' => 'flex items-center gap-2 rounded-2xl bg-red-50/90 px-5 py-3 text-sm text-red-700 ring-1 ring-red-200']) }}>
            <span class="h-2 w-2 rounded-full bg-red-500"></span>
            {{ $message }}
        </div>
    @enderror
@endforeach
