@php
    $user = Auth::user();

    $links = [['dashboard', 'dashboard', 'Dashboard']];

    if ($user->isWorker()) {
        $links[] = ['worker.profile.edit', 'worker.profile.*', 'Profil Kandidat'];
        $links[] = ['worker.sessions.index', 'worker.sessions.*', 'Sesi Tes'];
        $links[] = ['worker.payment.create', 'worker.payment.*', 'Pembayaran'];
    }

    if ($user->isLpk() || $user->isCompany() || $user->isSuperAdmin()) {
        $links[] = ['candidates.index', 'candidates.*', 'Kandidat'];
    }

    if ($user->isLpk()) {
        $links[] = ['lpk.recruitments.index', 'lpk.recruitments.*', 'Permintaan PT'];
        $links[] = ['lpk.categories.index', 'lpk.categories.*', 'Bank Soal'];
        $links[] = ['lpk.exams.index', 'lpk.exams.*', 'Sesi Tes'];
    }

    if ($user->isCompany()) {
        $links[] = ['company.recruitments.index', 'company.recruitments.*', 'Rekrutmen'];
        $links[] = ['company.sessions.index', 'company.sessions.*', 'Sesi Tes'];
        $links[] = ['company.interviews.index', 'company.interviews.*', 'Wawancara & Medical'];
    }

    if ($user->isSuperAdmin()) {
        $links[] = ['lpk.categories.index', 'lpk.categories.*', 'Bank Soal'];
        $links[] = ['admin.payments.index', 'admin.payments.*', 'Pembayaran'];
        $links[] = ['admin.organizations.index', 'admin.organizations.*', 'LPK & Perusahaan'];
        $links[] = ['admin.users.index', 'admin.users.*', 'Pengguna'];
    }
@endphp

@foreach ($links as [$route, $pattern, $label])
    <x-dynamic-component :component="$component" :href="route($route)" :active="request()->routeIs($pattern)">
        {{ $label }}
    </x-dynamic-component>
@endforeach
