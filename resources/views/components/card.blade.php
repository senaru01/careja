@props(['title' => null])

<div {{ $attributes->merge(['class' => 'panel']) }}>
    @if ($title || isset($actions))
        <div class="px-6 pt-6 flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-lg font-semibold text-slate-900">{{ $title }}</h3>
            {{ $actions ?? '' }}
        </div>
    @endif

    <div class="p-6">
        {{ $slot }}
    </div>
</div>
