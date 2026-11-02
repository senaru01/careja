<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\TwoFactorCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public const SESSION_USER_ID = 'two_factor.user_id';

    public const SESSION_REMEMBER = 'two_factor.remember';

    /**
     * Maximum wrong codes before the challenge is locked for a while.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Show the two factor code form.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->challengedUser($request)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Verify the code and log the user in.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $this->challengedUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $throttleKey = 'two-factor|'.$user->id;

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'code' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        if (! $user->hasValidTwoFactorCode($request->string('code'))) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'code' => 'Kode verifikasi salah atau sudah kedaluwarsa.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $user->resetTwoFactorCode();

        Auth::login($user, (bool) $request->session()->pull(self::SESSION_REMEMBER, false));

        $request->session()->forget(self::SESSION_USER_ID);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Send a new two factor code.
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $this->challengedUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $user->notify(new TwoFactorCode($user->generateTwoFactorCode()));

        return back()->with('status', 'Kode verifikasi baru telah dikirim ke email Anda.');
    }

    /**
     * Get the approved user that is in the middle of the login challenge.
     */
    private function challengedUser(Request $request): ?User
    {
        $userId = $request->session()->get(self::SESSION_USER_ID);

        $user = $userId ? User::find($userId) : null;

        return $user?->isApproved() ? $user : null;
    }
}
