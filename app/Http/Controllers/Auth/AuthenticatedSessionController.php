<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Notifications\TwoFactorCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle the first login step: check the password, then either log in
     * directly (two-step verification off) or send an OTP and show the challenge.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $request->validateCredentials();

        if (! $user->requiresTwoFactor()) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard', absolute: false));
        }

        $request->session()->regenerate();

        $request->session()->put([
            TwoFactorChallengeController::SESSION_USER_ID => $user->id,
            TwoFactorChallengeController::SESSION_REMEMBER => $request->boolean('remember'),
        ]);

        $user->notify(new TwoFactorCode($user->generateTwoFactorCode()));

        return redirect()->route('two-factor.challenge');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
