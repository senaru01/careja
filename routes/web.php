<?php

use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\UserApprovalController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CandidatePhotoController;
use App\Http\Controllers\Company\ExamSessionController as CompanyExamSessionController;
use App\Http\Controllers\Company\InterviewController;
use App\Http\Controllers\Company\InterviewPaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\Lpk\AttemptEvaluationController;
use App\Http\Controllers\Lpk\BankQuestionController;
use App\Http\Controllers\Lpk\BankQuestionImportController;
use App\Http\Controllers\Lpk\ExamBankQuestionController;
use App\Http\Controllers\Lpk\ExamController;
use App\Http\Controllers\Lpk\ExamPublicationController;
use App\Http\Controllers\Lpk\ExamQuestionController;
use App\Http\Controllers\Lpk\ExamRescheduleController;
use App\Http\Controllers\Lpk\QuestionCategoryController;
use App\Http\Controllers\MidtransNotificationController;
use App\Http\Controllers\PaymentDemoController;
use App\Http\Controllers\PaymentStatusController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Worker\CandidateProfileController;
use App\Http\Controllers\Worker\DocumentController as WorkerDocumentController;
use App\Http\Controllers\Worker\ExamAttemptController;
use App\Http\Controllers\Worker\ExamSessionController;
use App\Http\Controllers\Worker\MedicalPaymentController;
use App\Http\Controllers\Worker\PaymentController as WorkerPaymentController;
use App\Http\Controllers\Worker\PhotoController;
use App\Http\Controllers\Worker\QrisPaymentController;
use App\Http\Controllers\Worker\SkillController;
use App\Http\Controllers\Worker\WorkExperienceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Midtrans payment notifications (CSRF-exempt, verified by signature).
Route::post('midtrans/notification', MidtransNotificationController::class)
    ->middleware('throttle:60,1')
    ->name('midtrans.notification');

// Payment demo for the Midtrans review: real QRIS charges for every catalog
// item, behind a secret key in the URL, without login.
Route::middleware('payment-demo')->prefix('demo-pembayaran/{demoKey}')->name('payment-demo.')->group(function () {
    Route::get('/', [PaymentDemoController::class, 'index'])->name('index');
    Route::post('/', [PaymentDemoController::class, 'store'])->middleware('throttle:10,1')->name('store');
    Route::get('bayar/{payment}', [PaymentDemoController::class, 'show'])->name('show');
    Route::get('bayar/{payment}/status', [PaymentDemoController::class, 'status'])->middleware('throttle:30,1')->name('status');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('kandidat/{candidate}/foto', [CandidatePhotoController::class, 'show'])->name('candidates.photo');
    Route::get('pembayaran/{payment}/status', PaymentStatusController::class)->middleware('throttle:30,1')->name('payments.status');

    /*
     * Job seekers.
     */
    Route::middleware('role:worker')->prefix('pekerja')->name('worker.')->group(function () {
        Route::get('profil', [CandidateProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profil', [CandidateProfileController::class, 'update'])->name('profile.update');
        Route::post('profil/foto', [PhotoController::class, 'store'])->name('photo.store');
        Route::delete('profil/foto', [PhotoController::class, 'destroy'])->name('photo.destroy');

        Route::post('skills', [SkillController::class, 'store'])->name('skills.store');
        Route::delete('skills/{skill}', [SkillController::class, 'destroy'])->name('skills.destroy');

        Route::post('pengalaman', [WorkExperienceController::class, 'store'])->name('experiences.store');
        Route::delete('pengalaman/{experience}', [WorkExperienceController::class, 'destroy'])->name('experiences.destroy');

        Route::post('dokumen', [WorkerDocumentController::class, 'store'])->name('documents.store');
        Route::delete('dokumen/{document}', [WorkerDocumentController::class, 'destroy'])->name('documents.destroy');

        Route::get('pembayaran', [WorkerPaymentController::class, 'create'])->name('payment.create');
        Route::post('pembayaran', [WorkerPaymentController::class, 'store'])->name('payment.store');
        Route::post('pembayaran/qris', [QrisPaymentController::class, 'store'])->middleware('throttle:5,1')->name('payment.qris.store');

        Route::get('sesi-tes', [ExamSessionController::class, 'index'])->name('sessions.index');
        Route::get('sesi-tes/{exam}', [ExamSessionController::class, 'show'])->name('sessions.show');
        Route::post('sesi-tes/{exam}/daftar', [ExamSessionController::class, 'store'])->middleware('throttle:10,1')->name('sessions.register');
        Route::post('sesi-tes/{exam}/keluar', [ExamSessionController::class, 'withdraw'])->name('sessions.withdraw');

        Route::post('wawancara/{interview}/medical/bayar', [MedicalPaymentController::class, 'store'])->middleware('throttle:5,1')->name('medical-payment.store');

        Route::get('tes/{attempt}', [ExamAttemptController::class, 'show'])->name('attempts.show');
        Route::post('tes/{attempt}/mulai', [ExamAttemptController::class, 'start'])->name('attempts.start');
        Route::post('tes/{attempt}/kirim', [ExamAttemptController::class, 'submit'])->name('attempts.submit');
    });

    /*
     * Candidate list, shared by LPK, company and super admin.
     */
    Route::middleware('role:lpk,company,super_admin')->group(function () {
        Route::get('kandidat', [CandidateController::class, 'index'])->name('candidates.index');
        Route::get('kandidat/{candidate}', [CandidateController::class, 'show'])->name('candidates.show');
    });

    /*
     * Question bank: LPK staff for their own LPK, super admin for every LPK.
     */
    Route::middleware('role:lpk,super_admin')->prefix('lpk')->name('lpk.')->group(function () {
        Route::get('bank-soal', [QuestionCategoryController::class, 'index'])->name('categories.index');
        Route::get('bank-soal/template-excel', [BankQuestionImportController::class, 'template'])->name('bank.template');
        Route::post('bank-soal', [QuestionCategoryController::class, 'store'])->name('categories.store');
        Route::get('bank-soal/{category}', [QuestionCategoryController::class, 'show'])->name('categories.show');
        Route::get('bank-soal/{category}/ubah', [QuestionCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('bank-soal/{category}', [QuestionCategoryController::class, 'update'])->name('categories.update');
        Route::delete('bank-soal/{category}', [QuestionCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('bank-soal/{category}/import', [BankQuestionImportController::class, 'store'])->name('categories.import');

        Route::scopeBindings()->group(function () {
            Route::post('bank-soal/{category}/soal', [BankQuestionController::class, 'store'])->name('categories.questions.store');
            Route::get('bank-soal/{category}/soal/{question}/ubah', [BankQuestionController::class, 'edit'])->name('categories.questions.edit');
            Route::put('bank-soal/{category}/soal/{question}', [BankQuestionController::class, 'update'])->name('categories.questions.update');
            Route::delete('bank-soal/{category}/soal/{question}', [BankQuestionController::class, 'destroy'])->name('categories.questions.destroy');
        });
    });

    /*
     * LPK.
     */
    Route::middleware('role:lpk')->prefix('lpk')->name('lpk.')->group(function () {
        Route::resource('ujian', ExamController::class)->names('exams')->parameters(['ujian' => 'exam']);
        Route::post('ujian/{exam}/soal', [ExamQuestionController::class, 'store'])->name('exams.questions.store');
        Route::delete('ujian/{exam}/soal/{question}', [ExamQuestionController::class, 'destroy'])
            ->scopeBindings()
            ->name('exams.questions.destroy');
        Route::post('ujian/{exam}/ambil-bank-soal', [ExamBankQuestionController::class, 'store'])->name('exams.bank.store');
        Route::delete('ujian/{exam}/soal', [ExamBankQuestionController::class, 'destroy'])->name('exams.questions.clear');
        Route::post('ujian/{exam}/terbitkan', [ExamPublicationController::class, 'store'])->middleware('throttle:5,1')->name('exams.publish');
        Route::post('ujian/{exam}/jadwal', [ExamRescheduleController::class, 'store'])->name('exams.reschedule');

        Route::put('hasil-tes/{attempt}/evaluasi', [AttemptEvaluationController::class, 'update'])->name('attempts.evaluate');
    });

    /*
     * Company.
     */
    Route::middleware('role:company')->prefix('perusahaan')->name('company.')->group(function () {
        Route::get('sesi-tes', [CompanyExamSessionController::class, 'index'])->name('sessions.index');
        Route::get('sesi-tes/{exam}', [CompanyExamSessionController::class, 'show'])->name('sessions.show');
        Route::post('sesi-tes/{exam}/bayar', [InterviewPaymentController::class, 'store'])->middleware('throttle:5,1')->name('sessions.payment.store');
        Route::post('sesi-tes/{exam}/undang', [InterviewController::class, 'store'])->name('interviews.store');

        Route::get('wawancara', [InterviewController::class, 'index'])->name('interviews.index');
        Route::put('wawancara/{interview}', [InterviewController::class, 'update'])->name('interviews.update');
        Route::put('wawancara/{interview}/medical', [InterviewController::class, 'medical'])->name('interviews.medical');
    });

    /*
     * Super admin.
     */
    Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('users', [UserApprovalController::class, 'index'])->name('users.index');
        Route::patch('users/{user}/approve', [UserApprovalController::class, 'approve'])->name('users.approve');
        Route::patch('users/{user}/revoke', [UserApprovalController::class, 'revoke'])->name('users.revoke');
        Route::patch('users/{user}/two-factor', [UserApprovalController::class, 'toggleTwoFactor'])->name('users.two-factor');
        Route::patch('users/{user}/password', [UserApprovalController::class, 'resetPassword'])->middleware('throttle:10,1')->name('users.password');
        Route::delete('users/{user}', [UserApprovalController::class, 'destroy'])->name('users.destroy');

        Route::get('pembayaran', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::get('pembayaran/{payment}/bukti', [AdminPaymentController::class, 'proof'])->name('payments.proof');
        Route::patch('pembayaran/{payment}', [AdminPaymentController::class, 'update'])->name('payments.update');

        Route::get('organisasi', [OrganizationController::class, 'index'])->name('organizations.index');
        Route::get('organisasi/tambah', [OrganizationController::class, 'create'])->name('organizations.create');
        Route::post('organisasi', [OrganizationController::class, 'store'])->name('organizations.store');
    });
});

require __DIR__.'/auth.php';
