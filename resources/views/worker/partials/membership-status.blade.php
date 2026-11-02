{{-- Membership banner. Expects $activeMembership (?Payment) and $membershipExpired (bool). --}}
@if ($activeMembership)
    @php
        $start = $activeMembership->verified_at;
        $end = $activeMembership->expires_at;
        $elapsed = $start && $end ? min(100, max(0, (int) round($start->diffInSeconds(now()) / max(1, $start->diffInSeconds($end)) * 100))) : 0;
        $daysLeft = (int) now()->diffInDays($end);
    @endphp
    <div class="panel p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    <span class="text-lg font-semibold text-slate-900">Akun Aktif</span>
                </div>
                <p class="mt-1 text-sm text-slate-500">Profil Anda terlihat oleh LPK dan perusahaan sampai <strong class="text-slate-700">{{ $end->translatedFormat('d F Y') }}</strong>.</p>
            </div>
            <div class="text-right">
                <div class="text-4xl font-semibold tracking-tight text-slate-900">{{ $daysLeft }}</div>
                <div class="text-xs text-slate-500">hari tersisa</div>
            </div>
        </div>
        <div class="mt-4 h-2.5 rounded-full bg-slate-200 overflow-hidden">
            <div class="h-full rounded-full bg-blue-600" style="width: {{ 100 - $elapsed }}%"></div>
        </div>
    </div>
@elseif ($membershipExpired)
    <div class="panel p-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>
                <span class="text-lg font-semibold text-slate-900">Masa Aktif Habis</span>
            </div>
            <p class="mt-1 text-sm text-slate-500">Profil Anda tidak lagi terlihat oleh LPK dan perusahaan. Perpanjang untuk mengaktifkan kembali.</p>
        </div>
        @unless (request()->routeIs('worker.payment.*'))
            <a href="{{ route('worker.payment.create') }}"><x-primary-button type="button">Perpanjang</x-primary-button></a>
        @endunless
    </div>
@endif
