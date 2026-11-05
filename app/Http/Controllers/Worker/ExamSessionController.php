<?php

namespace App\Http\Controllers\Worker;

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentProofRequest;
use App\Models\Exam;
use App\Models\ExamPosition;
use App\Services\ExamSessions;
use App\Services\ManualPayments;
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

        $exam->load(['organization', 'company', 'reschedules', 'positions'])->loadCount('participants');
        $user->loadMissing(['candidateProfile', 'workExperiences']);
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
            'rejectedPayment' => $attempt?->isRegistered() ? null : $exam->latestRejectedPayment(PaymentPurpose::ExamRegistration, $user),
            'qrisAvailable' => $qris->isAvailable(),
            'tokens' => $user->availableExamTokens(),
            'fee' => config('careja.exam_registration_fee'),
            'positionChecks' => $exam->positions->mapWithKeys(fn ($position) => [$position->id => $position->unmetRequirements($user)]),
            'rankingGroups' => $exam->hasEnded() ? $exam->rankingGroups() : collect(),
            'interviews' => $exam->hasEnded() ? $exam->interviews()->get()->keyBy('user_id') : collect(),
        ]);
    }

    /**
     * Register for the session: confirm the qualifications, then pay by QRIS,
     * with a token, or by transfer with an uploaded proof (verified by the admin).
     */
    public function store(Request $request, Exam $exam, ExamSessions $sessions, QrisPayments $qris, ManualPayments $manualPayments): RedirectResponse
    {
        Gate::authorize('register', $exam);

        $user = $request->user();
        $validated = $request->validate([
            'exam_position_id' => [
                Rule::requiredIf(fn () => $exam->positions()->exists()),
                'nullable',
                'integer',
                Rule::exists(ExamPosition::class, 'id')->where('exam_id', $exam->id),
            ],
            'qualification_confirmed' => ['accepted'],
            'pay_with' => ['required', Rule::in(['qris', 'token', 'manual'])],
            'proof' => ['exclude_unless:pay_with,manual', ...PaymentProofRequest::proofRules()],
        ], [
            'proof.required' => 'Pilih file bukti transfer terlebih dahulu.',
            'exam_position_id.required' => 'Pilih posisi yang Anda lamar.',
            'qualification_confirmed.accepted' => 'Centang pernyataan bahwa Anda memenuhi kualifikasi sesi ini.',
        ]);

        $position = isset($validated['exam_position_id']) ? $exam->positions()->find($validated['exam_position_id']) : null;
        $unmet = $position?->unmetRequirements($user->load(['candidateProfile', 'workExperiences'])) ?? [];

        if ($unmet !== []) {
            return back()->withInput()->withErrors(['exam_position_id' => 'Anda belum memenuhi syarat posisi '.$position->position.': '.implode('; ', $unmet).'.']);
        }

        $alreadyPaying = $user->payments()->where('exam_id', $exam->id)
            ->where('purpose', PaymentPurpose::ExamRegistration)->where('status', PaymentStatus::Pending)->exists();

        if ($alreadyPaying) {
            return redirect()->route('worker.sessions.show', $exam);
        }

        $attempt = $sessions->prepareRegistration($user, $exam, $position);

        if ($validated['pay_with'] === 'token') {
            if (! $sessions->registerWithToken($attempt)) {
                return back()->withErrors(['pay_with' => 'Anda tidak punya token yang tersedia.']);
            }

            return redirect()->route('worker.sessions.show', $exam)
                ->with('status', 'Terdaftar menggunakan 1 token. Sampai jumpa di hari tes!');
        }

        if ($validated['pay_with'] === 'manual') {
            $manualPayments->submit($user, PaymentPurpose::ExamRegistration, $request->file('proof'), $exam);

            return redirect()->route('worker.sessions.show', $exam)
                ->with('status', 'Bukti transfer terkirim. Anda terdaftar otomatis setelah admin memverifikasi pembayaran.');
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
