<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\Tenancy;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Validate the credentials without logging the user in (first step of login)
     * and make sure the account has been approved by a super admin.
     *
     * @throws ValidationException
     */
    public function validateCredentials(): User
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::validate($this->only('email', 'password'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        /** @var User $user */
        $user = Auth::getLastAttempted();

        if (! $user->isApproved()) {
            throw ValidationException::withMessages([
                'email' => 'Akun Anda belum disetujui oleh super admin. Silakan tunggu konfirmasi.',
            ]);
        }

        if (! $user->canLogInOn(app(Tenancy::class)->current())) {
            throw ValidationException::withMessages([
                'email' => $this->wrongSiteMessage($user),
            ]);
        }

        return $user;
    }

    /**
     * Tell the user where their account lives, since every LPK and company
     * logs in on its own subdomain.
     */
    private function wrongSiteMessage(User $user): string
    {
        $home = $user->isSuperAdmin() ? null : $user->organization;

        if (! $user->isSuperAdmin() && ! $home?->subdomain) {
            return 'Akun ini belum terhubung ke alamat LPK / perusahaan mana pun. Silakan hubungi admin Careja.';
        }

        return 'Akun ini tidak terdaftar di sini. Silakan login melalui '.app(Tenancy::class)->hostFor($home).'.';
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
