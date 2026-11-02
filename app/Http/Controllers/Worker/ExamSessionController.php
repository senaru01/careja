<?php

namespace App\Http\Controllers\Worker;

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\ExamSessions;
use App\Services\QrisPayments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExamSessionController extends Controller
{
    /**
     * Open sessions to join, plus the sessions the worker is part of.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('worker.sessions.index', [
            'openSessions' => Exam::query()
                ->whereNotNull('published_at')
                ->where('starts_at', '>', now())
                ->with(['organization', 'company'])
                ->withCount('participants')
                ->orderBy('starts_at')
                ->get(),
            'myAttempts' => $user->examAttempts()->with('exam.organization', 'exam.company')->get(),
            'tokens' => $user->availableExamTokens(),
            'fee' => config('careja.exam_registration_fee'),
            'isActiveMember' => $user->hasActiveMembership(),
        ]);
    }

    public function show(Request $request, Exam $exam, QrisPayments $qris): View
    {
        $user = $request->user();
        abort_unless($exam->isPublished(), 404);

        $qris->syncPendingOf($user);

        $exam->load(['organization', 'company', 'reschedules'])->loadCount('participants');
        $attempt = $exam->attempts()->where('user_id', $user->id)->first()?->setRelation('exam', $exam);
        $attempt?->finalizeIfExpired();

        return view('worker.sessions.show', [
            'exam' => $exam,
            'attempt' => $attempt,
            'registerCheck' => Gate::inspect('register', $exam),
            'pendingPayment' => $user->payments()
                ->where('purpose', PaymentPurpose::ExamRegistration)
                ->where('exam_id', $exam->id)
                ->where('status', PaymentStatus::Pending)
                ->first(),
            'qrisAvailable' => $qris->isAvailable(),
            'tokens' => $user->availableExamTokens(),
            'fee' => config('careja.exam_registration_fee'),
            'ranking' => $exam->hasEnded() ? $exam->ranking() : collect(),
            'interviews' => $exam->hasEnded() ? $exam->interviews()->get()->keyBy('user_id') : collect(),
        ]);
    }

    /**
     * Register for the session: confirm the qualifications, then pay by QRIS or with a token.
     */
    public function store(Request $request, Exam $exam, ExamSessions $sessions, QrisPayments $qris): RedirectResponse
    {
        Gate::authorize('register', $exam);

        $user = $request->user();
        $validated = $request->validate([
            'qualification_confirmed' => ['accepted'],
            'pay_with' => ['required', Rule::in(['qris', 'token'])],
        ], ['qualification_confirmed.accepted' => 'Centang pernyataan bahwa Anda memenuhi kualifikasi sesi ini.']);

        $alreadyPaying = $user->payments()->where('exam_id', $exam->id)
            ->where('purpose', PaymentPurpose::ExamRegistration)->where('status', PaymentStatus::Pending)->exists();

        if ($alreadyPaying) {
            return redirect()->route('worker.sessions.show', $exam);
        }

        $attempt = $sessions->prepareRegistration($user, $exam);

        if ($validated['pay_with'] === 'token') {
            if (! $sessions->registerWithToken($attempt)) {
                return back()->withErrors(['pay_with' => 'Anda tidak punya token yang tersedia.']);
            }

            return redirect()->route('worker.sessions.show', $exam)
                ->with('status', 'Terdaftar menggunakan 1 token. Sampai jumpa di hari tes!');
        }

        abort_unless($qris->isAvailable(), 503, 'Pembayaran QRIS otomatis belum tersedia.');

        if (! $qris->startExamRegistration($user, $exam)) {
            return back()->withErrors(['pay_with' => 'QRIS gagal dibuat. Silakan coba lagi beberapa saat lagi.']);
        }

        return redirect()->route('worker.sessions.show', $exam);
    }

    /**
     * Leave a postponed session and receive a token for another one.
     */
    public function withdraw(Request $request, Exam $exam, ExamSessions $sessions): RedirectResponse
    {
        $attempt = $exam->attempts()->where('user_id', $request->user()->id)->firstOrFail()->setRelation('exam', $exam);

        Gate::authorize('withdraw', $attempt);

        $sessions->withdraw($attempt);

        return redirect()->route('worker.sessions.index')
            ->with('status', 'Anda keluar dari sesi "'.$exam->title.'" dan mendapat 1 token untuk sesi tes lain.');
    }
}
