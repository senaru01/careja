<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Services\Tenancy;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration form on an LPK's subdomain. Elsewhere (root
     * domain, company subdomain) list the partner LPKs to register at.
     */
    public function create(Tenancy $tenancy): View
    {
        $lpk = $tenancy->current();

        if (! $lpk?->isLpk()) {
            return view('auth.choose-lpk', [
                'lpks' => Organization::lpks()->whereNotNull('subdomain')->orderBy('name')->get(),
            ]);
        }

        return view('auth.register', ['lpk' => $lpk]);
    }

    /**
     * Register a job seeker as a member of the LPK whose subdomain they are
     * on. The account is active right away; the candidate shows up in the
     * candidate list once the membership fee is verified.
     *
     * @throws ValidationException
     */
    public function store(Request $request, Tenancy $tenancy): RedirectResponse
    {
        $lpk = $tenancy->current();

        abort_unless($lpk?->isLpk(), 404);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.unique' => 'Email ini sudah terdaftar. Satu email hanya bisa menjadi member satu LPK; gunakan email lain atau login di alamat LPK Anda.',
        ]);

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->forceFill([
            'role' => UserRole::Worker,
            'organization_id' => $lpk->id,
            'approved_at' => now(),
        ])->save();

        event(new Registered($user));

        return redirect()->route('login')
            ->with('status', 'Pendaftaran berhasil. Silakan login, lengkapi profil, lalu bayar biaya keanggotaan agar masuk daftar kandidat.');
    }
}
