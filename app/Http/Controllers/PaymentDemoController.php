<?php

namespace App\Http\Controllers;

use App\Enums\PaymentPurpose;
use App\Enums\UserRole;
use App\Models\Payment;
use App\Models\User;
use App\Services\QrisPayments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Demo page for payment gateway reviewers: every catalog item can be paid
 * with a real Midtrans QRIS charge, without an account or the recruitment
 * flow. Demo payments are recorded but never activate anything.
 */
class PaymentDemoController extends Controller
{
    public const DEMO_EMAIL = 'demo-midtrans@careja.id';

    public function index(QrisPayments $qris): View
    {
        return view('payment-demo.index', [
            'items' => self::catalog(),
            'payment' => null,
            'qrisAvailable' => $qris->isAvailable(),
            'recentPayments' => Payment::query()->where('is_demo', true)->latest('id')->limit(10)->get(),
        ]);
    }

    public function store(Request $request, QrisPayments $qris): RedirectResponse
    {
        $validated = $request->validate(['purpose' => ['required', Rule::enum(PaymentPurpose::class)]]);

        abort_unless($qris->isAvailable(), 503, 'Pembayaran QRIS otomatis belum tersedia.');

        $payment = $qris->startDemo($this->demoUser(), PaymentPurpose::from($validated['purpose']));

        if (! $payment) {
            return back()->withErrors(['payment' => 'QRIS gagal dibuat oleh Midtrans. Silakan coba lagi beberapa saat lagi.']);
        }

        return redirect()->route('payment-demo.show', ['demoKey' => config('careja.payment_demo_key'), 'payment' => $payment]);
    }

    public function show(Payment $payment, QrisPayments $qris): View
    {
        abort_unless($payment->is_demo, 404);

        return view('payment-demo.index', [
            'items' => self::catalog(),
            'payment' => $qris->sync($payment),
            'qrisAvailable' => $qris->isAvailable(),
            'recentPayments' => Payment::query()->where('is_demo', true)->latest('id')->limit(10)->get(),
        ]);
    }

    /**
     * Polled by the QRIS waiter on the demo page.
     */
    public function status(Payment $payment, QrisPayments $qris): JsonResponse
    {
        abort_unless($payment->is_demo, 404);

        $payment = $qris->sync($payment);

        return response()->json(['status' => $payment->status->value, 'active' => false]);
    }

    /**
     * Every payment in the public catalog: who pays it and per what.
     *
     * @return array<int, array{purpose: PaymentPurpose, payer: string, unit: string, description: string}>
     */
    public static function catalog(): array
    {
        return [
            ['purpose' => PaymentPurpose::Membership, 'payer' => 'Pencari Kerja', 'unit' => 'per tahun', 'description' => 'Keanggotaan '.config('careja.membership_months').' bulan, profil terlihat oleh LPK dan perusahaan.'],
            ['purpose' => PaymentPurpose::ExamRegistration, 'payer' => 'Pencari Kerja', 'unit' => 'per sesi tes', 'description' => 'Ikut satu sesi tes online, nilai dan peringkat terbuka.'],
            ['purpose' => PaymentPurpose::ExamSession, 'payer' => 'LPK', 'unit' => 'per sesi tes', 'description' => 'Menerbitkan satu sesi tes untuk perusahaan mitra.'],
            ['purpose' => PaymentPurpose::InterviewAccess, 'payer' => 'Perusahaan', 'unit' => 'per sesi tes', 'description' => 'Membuka peringkat final dan mengundang kandidat wawancara sesuai kuota.'],
            ['purpose' => PaymentPurpose::MedicalCheckup, 'payer' => 'Kandidat', 'unit' => 'per kandidat', 'description' => 'Pemeriksaan kesehatan tahap akhir setelah lolos wawancara.'],
        ];
    }

    /**
     * The account demo payments are booked on. It can never log in: it is
     * not approved and its password is random.
     */
    private function demoUser(): User
    {
        $user = User::firstOrNew(['email' => self::DEMO_EMAIL]);

        if (! $user->exists) {
            $user->forceFill([
                'name' => 'Demo Midtrans',
                'password' => Str::random(40),
                'role' => UserRole::Worker,
            ])->save();
        }

        return $user;
    }
}
