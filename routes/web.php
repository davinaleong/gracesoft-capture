<?php

use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\AdminComplianceController;
use App\Http\Controllers\AdminEmailVerificationController;
use App\Http\Controllers\AdminPasswordResetController;
use App\Http\Controllers\AdminSessionController;
use App\Http\Controllers\FormManagementController;
use App\Http\Controllers\EnquiryNoteController;
use App\Http\Controllers\EnquiryReplyController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\InsightsController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\UserEmailVerificationController;
use App\Http\Controllers\UserPasswordResetController;
use App\Http\Controllers\UserSessionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::guard('admin')->check()) {
        return redirect()->route('admin.compliance.index');
    }

    if (Auth::guard('web')->check()) {
        return redirect()->route('manage.forms.index');
    }

    return redirect()->route('login');
});

Route::get('/components', function () {
    return view('components');
});

Route::get('/form/{token}', [PublicFormController::class, 'show'])
    ->name('forms.show');

Route::post('/form/{token}/submit', [PublicFormController::class, 'submit'])
    ->middleware('throttle:form-submissions')
    ->name('forms.submit');

Route::get('/support', [FeedbackController::class, 'create'])->name('support.create');
Route::post('/support', [FeedbackController::class, 'store'])->name('support.store');

Route::middleware('guest:web')->group(function () {
    Route::get('/register', [UserSessionController::class, 'register'])->name('register');
    Route::post('/register', [UserSessionController::class, 'storeRegistration'])->name('register.store');
    Route::get('/login', [UserSessionController::class, 'create'])->name('login');
    Route::post('/login', [UserSessionController::class, 'store'])->name('login.store');

    Route::get('/forgot-password', [UserPasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [UserPasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [UserPasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [UserPasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [UserSessionController::class, 'destroy'])
    ->middleware('auth:web')
    ->name('logout');

Route::middleware('auth:web')->group(function () {
    Route::get('/email/verify', [UserEmailVerificationController::class, 'notice'])
        ->name('verification.notice');
    Route::post('/email/verification-notification', [UserEmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::get('/email/verify/{id}/{hash}', [UserEmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminSessionController::class, 'create'])->name('login');
        Route::post('/login', [AdminSessionController::class, 'store'])->name('login.store');

        Route::get('/forgot-password', [AdminPasswordResetController::class, 'requestForm'])->name('password.request');
        Route::post('/forgot-password', [AdminPasswordResetController::class, 'sendResetLink'])->name('password.email');
        Route::get('/reset-password/{token}', [AdminPasswordResetController::class, 'resetForm'])->name('password.reset');
        Route::post('/reset-password', [AdminPasswordResetController::class, 'reset'])->name('password.update');
    });

    Route::post('/logout', [AdminSessionController::class, 'destroy'])
        ->middleware('auth:admin')
        ->name('logout');

    Route::middleware('auth:admin')->group(function () {
        Route::get('/email/verify', [AdminEmailVerificationController::class, 'notice'])
            ->name('verification.notice');
        Route::post('/email/verification-notification', [AdminEmailVerificationController::class, 'send'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
        Route::get('/email/verify/{id}/{hash}', [AdminEmailVerificationController::class, 'verify'])
            ->middleware('signed')
            ->name('verification.verify');
    });
});

Route::prefix('manage/forms')->middleware(['auth.any', 'access.context'])->name('manage.forms.')->group(function () {
    Route::get('/', [FormManagementController::class, 'index'])->name('index');
    Route::get('/create', [FormManagementController::class, 'create'])->name('create');
    Route::post('/', [FormManagementController::class, 'store'])->name('store');
    Route::get('/{form}/edit', [FormManagementController::class, 'edit'])->name('edit');
    Route::put('/{form}', [FormManagementController::class, 'update'])->name('update');
    Route::post('/{form}/toggle-active', [FormManagementController::class, 'toggleActive'])->name('toggle-active');
});

Route::prefix('inbox')->middleware(['auth.any', 'access.context'])->name('inbox.')->group(function () {
    Route::get('/', [InboxController::class, 'index'])->name('index');
    Route::get('/{enquiry}', [InboxController::class, 'show'])->name('show');
    Route::post('/{enquiry}/status', [InboxController::class, 'updateStatus'])->name('status.update');
    Route::post('/{enquiry}/notes', [EnquiryNoteController::class, 'store'])->name('notes.store');
    Route::post('/{enquiry}/replies', [EnquiryReplyController::class, 'store'])->name('replies.store');
});

Route::prefix('insights')->middleware(['auth.any', 'access.context'])->name('insights.')->group(function () {
    Route::get('/', [InsightsController::class, 'index'])->name('index');
});

Route::prefix('integrations')->middleware(['auth.any', 'access.context'])->name('integrations.')->group(function () {
    Route::get('/', [IntegrationController::class, 'index'])->name('index');
});

Route::prefix('settings/collaborators')->middleware(['auth', 'access.context'])->name('collaborators.')->group(function () {
    Route::get('/', [CollaboratorController::class, 'index'])->name('index');
    Route::post('/', [CollaboratorController::class, 'store'])->name('store');
    Route::post('/{invitation}/resend', [CollaboratorController::class, 'resend'])->name('resend');
    Route::post('/{invitation}/revoke', [CollaboratorController::class, 'revoke'])->name('revoke');
    Route::post('/memberships/{membershipToRemove}/remove', [CollaboratorController::class, 'remove'])->name('remove');
});

Route::get('/collaborators/invitations/{invitation}/{token}/accept', [CollaboratorController::class, 'accept'])
    ->middleware(['auth', 'verified.guard:web,collaborator_acceptance'])
    ->name('collaborators.accept');

Route::prefix('admin/compliance')->middleware(['auth.any', 'access.context', 'admin.session.secure'])->name('admin.compliance.')->group(function () {
    Route::get('/', [AdminComplianceController::class, 'index'])->name('index');
    Route::post('/break-glass/request', [AdminComplianceController::class, 'requestBreakGlass'])
        ->middleware('verified.guard:admin,sensitive_admin_operation')
        ->name('break-glass.request');
    Route::post('/break-glass/{breakGlassApproval}/approve', [AdminComplianceController::class, 'approveBreakGlass'])
        ->middleware('verified.guard:admin,sensitive_admin_operation')
        ->name('break-glass.approve');
    Route::post('/dsr/{dataSubjectRequest}/status', [AdminComplianceController::class, 'updateDsrStatus'])
        ->middleware('verified.guard:admin,sensitive_admin_operation')
        ->name('dsr.update');
    Route::post('/dsr/{dataSubjectRequest}/process', [AdminComplianceController::class, 'processDsr'])
        ->middleware('verified.guard:admin,sensitive_admin_operation')
        ->name('dsr.process');
    Route::post('/administrators/{administrator}/recertify', [AdminComplianceController::class, 'recertifyAdministratorAccess'])
        ->middleware('verified.guard:admin,sensitive_admin_operation')
        ->name('administrators.recertify');
});
