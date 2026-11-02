@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border-slate-200 bg-white text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500']) }}>
