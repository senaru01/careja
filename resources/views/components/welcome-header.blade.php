@props(['user'])

<h1 class="text-4xl sm:text-5xl font-semibold tracking-tight text-slate-900">
    Selamat datang, {{ \Illuminate\Support\Str::of($user->name)->before(' ') }}
</h1>
