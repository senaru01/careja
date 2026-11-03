<?php

namespace App\Http\Controllers;

use App\Enums\InterviewStatus;
use App\Enums\MedicalStatus;
use App\Enums\UserRole;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use App\Services\QrisPayments;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the dashboard that matches the user's role.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return match ($user->role) {
            UserRole::Worker => $this->worker($user),
            UserRole::Lpk => $this->lpk($user),
            UserRole::Company => $this->company($user),
            UserRole::SuperAdmin => $this->superAdmin(),
        };
    }

    private function worker(User $user): View
    {
        $user->load(['candidateProfile', 'examAttempts.exam.organization', 'examAttempts.exam.company', 'interviews.organization']);
        $user->examAttempts->each->finalizeIfExpired();

        $joinedExamIds = $user->examAttempts->pluck('exam_id');

        return view('dashboard.worker', [
            'user' => $user,
            'openSessions' => Exam::query()
                ->whereNotNull('published_at')
                ->where('starts_at', '>', now())
                ->whereNotIn('id', $joinedExamIds)
                ->with(['organization', 'company'])
                ->orderBy('starts_at')
                ->limit(5)
                ->get(),
            'tokens' => $user->availableExamTokens(),
            'stage' => $user->candidateStage(),
            'profileComplete' => $user->hasCompleteCandidateProfile(),
            'latestPayment' => $user->payments()->membership()->first(),
            'isActiveCandidate' => $user->hasActiveMembership(),
            'activeMembership' => $user->activeMembership(),
            'membershipExpired' => $user->hasExpiredMembership(),
            'qrisAvailable' => app(QrisPayments::class)->isAvailable(),
        ]);
    }

    private function lpk(User $user): View
    {
        $organizationId = $user->organization_id;
        $attempts = ExamAttempt::whereRelation('exam', 'organization_id', $organizationId);

        return view('dashboard.lpk', [
            'organization' => $user->organization,
            'stats' => [
                'Kandidat Aktif' => User::activeCandidates()->count(),
                'Sesi Tes Terbit' => $user->organization->exams()->whereNotNull('published_at')->count(),
                'Tes Dikerjakan' => (clone $attempts)->whereNotNull('submitted_at')->count(),
                'Lulus Teori' => (clone $attempts)->where('passed', true)->count(),
            ],
            'upcomingSessions' => $user->organization->exams()
                ->whereNotNull('published_at')
                ->where('ends_at', '>', now())
                ->with('company')
                ->withCount('participants')
                ->orderBy('starts_at')
                ->limit(5)
                ->get(),
            'needsEvaluation' => (clone $attempts)->with(['user', 'exam'])
                ->whereNotNull('submitted_at')->whereNull('evaluation')
                ->latest('submitted_at')->limit(10)->get(),
            'recentAttempts' => (clone $attempts)->with(['user', 'exam'])
                ->whereNotNull('submitted_at')->latest('submitted_at')->limit(10)->get(),
        ]);
    }

    private function company(User $user): View
    {
        $interviews = $user->organization->interviews();
        $sessions = Exam::where('company_id', $user->organization_id)->whereNotNull('published_at');

        return view('dashboard.company', [
            'organization' => $user->organization,
            'stats' => [
                'Sesi Tes' => (clone $sessions)->count(),
                'Wawancara Terjadwal' => (clone $interviews)->where('status', InterviewStatus::Scheduled)->count(),
                'Menunggu Medical' => (clone $interviews)->where('status', InterviewStatus::Passed)->whereNull('medical_status')->count(),
                'Diterima Final' => (clone $interviews)->where('medical_status', MedicalStatus::Fit)->count(),
            ],
            'sessions' => (clone $sessions)->with('organization')->withCount('participants')
                ->latest('starts_at')->limit(6)->get(),
            'upcomingInterviews' => (clone $interviews)->with('user')
                ->where('status', InterviewStatus::Scheduled)->orderBy('scheduled_at')->limit(10)->get(),
        ]);
    }

    private function superAdmin(): View
    {
        return view('dashboard.admin', [
            'stats' => [
                'Pencari Kerja' => User::where('role', UserRole::Worker)->where('email', '!=', PaymentDemoController::DEMO_EMAIL)->count(),
                'Kandidat Aktif' => User::activeCandidates()->count(),
                'Pembayaran Menunggu' => Payment::pending()->count(),
                'Total Pendapatan' => 'Rp '.number_format(Payment::paid()->sum('amount'), 0, ',', '.'),
            ],
            'pendingPayments' => Payment::with('user')->pending()->oldest()->limit(10)->get(),
            'organizations' => Organization::withCount('users')->get(),
        ]);
    }
}
