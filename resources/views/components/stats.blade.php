@props(['stats' => []])

{{-- Large headline numbers with a small icon chip, as in the dashboard header. --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    @foreach ($stats as $label => $value)
        <div class="panel px-5 py-4 flex items-end justify-between gap-3">
            <div>
                <div class="h-9 w-9 rounded-full bg-white ring-1 ring-slate-200 flex items-center justify-center text-blue-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                </div>
                <div class="mt-2 text-xs text-slate-500">{{ $label }}</div>
            </div>
            <div class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900 text-right">{{ $value }}</div>
        </div>
    @endforeach
</div>
