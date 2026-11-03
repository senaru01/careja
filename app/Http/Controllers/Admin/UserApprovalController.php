<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentDemoController;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserApprovalController extends Controller
{
    /**
     * List users, optionally filtered by approval status.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');

        $users = User::query()
            ->where('email', '!=', PaymentDemoController::DEMO_EMAIL)
            ->when($status === 'pending', fn ($query) => $query->pending())
            ->when($status === 'approved', fn ($query) => $query->whereNotNull('approved_at'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'status' => $status,
            'pendingCount' => User::pending()->where('email', '!=', PaymentDemoController::DEMO_EMAIL)->count(),
            'twoFactorGloballyEnabled' => config('careja.two_factor_enabled'),
        ]);
    }

    /**
     * Approve a pending user.
     */
    public function approve(Request $request, User $user): RedirectResponse
    {
        $user->approve($request->user());

        return back()->with('status', "Akun {$user->email} telah disetujui.");
    }

    /**
     * Revoke the access of an approved user.
     */
    public function revoke(Request $request, User $user): RedirectResponse
    {
        if ($user->isSuperAdmin()) {
            return back()->withErrors(['user' => 'Akses super admin tidak dapat dicabut.']);
        }

        $user->revokeApproval();

        return back()->with('status', "Akses akun {$user->email} telah dicabut.");
    }

    /**
     * Turn two-step verification (email OTP at login) on or off for a user.
     */
    public function toggleTwoFactor(User $user): RedirectResponse
    {
        $user->forceFill(['two_factor_enabled' => ! $user->two_factor_enabled])->save();

        $state = $user->two_factor_enabled ? 'diaktifkan' : 'dimatikan';

        return back()->with('status', "Verifikasi 2 langkah untuk {$user->email} {$state}.");
    }

    /**
     * Replace a user's password with a random temporary one, shown once to
     * the admin to hand over. The user is logged out everywhere.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Ubah password Anda sendiri lewat menu Profil.']);
        }

        $temporaryPassword = Str::password(10, symbols: false);

        $user->forceFill([
            'password' => $temporaryPassword,
            'remember_token' => Str::random(60),
        ])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        return back()
            ->with('status', "Password {$user->email} telah direset.")
            ->with('temporaryPassword', ['email' => $user->email, 'password' => $temporaryPassword]);
    }

    /**
     * Reject (delete) a pending registration.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->isApproved()) {
            return back()->withErrors(['user' => 'Hanya pendaftaran yang belum disetujui yang dapat ditolak.']);
        }

        $user->delete();

        return back()->with('status', "Pendaftaran {$user->email} telah ditolak.");
    }
}
