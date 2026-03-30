<?php

use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\AdminComplianceController;
use App\Http\Controllers\AdminSessionController;
use App\Http\Controllers\FormManagementController;
use App\Http\Controllers\EnquiryNoteController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\UserSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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
});

Route::post('/logout', [UserSessionController::class, 'destroy'])
    ->middleware('auth:web')
    ->name('logout');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminSessionController::class, 'create'])->name('login');
        Route::post('/login', [AdminSessionController::class, 'store'])->name('login.store');
    });

    Route::post('/logout', [AdminSessionController::class, 'destroy'])
        ->middleware('auth:admin')
        ->name('logout');
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
});

Route::prefix('settings/collaborators')->middleware(['auth', 'access.context'])->name('collaborators.')->group(function () {
    Route::get('/', [CollaboratorController::class, 'index'])->name('index');
    Route::post('/', [CollaboratorController::class, 'store'])->name('store');
    Route::post('/{invitation}/resend', [CollaboratorController::class, 'resend'])->name('resend');
    Route::post('/{invitation}/revoke', [CollaboratorController::class, 'revoke'])->name('revoke');
    Route::post('/memberships/{membershipToRemove}/remove', [CollaboratorController::class, 'remove'])->name('remove');
});

Route::get('/collaborators/invitations/{invitation}/{token}/accept', [CollaboratorController::class, 'accept'])
    ->middleware('auth')
    ->name('collaborators.accept');

Route::prefix('admin/compliance')->middleware(['auth.any', 'access.context', 'admin.session.secure'])->name('admin.compliance.')->group(function () {
    Route::get('/', [AdminComplianceController::class, 'index'])->name('index');
    Route::post('/break-glass/request', [AdminComplianceController::class, 'requestBreakGlass'])->name('break-glass.request');
    Route::post('/break-glass/{breakGlassApproval}/approve', [AdminComplianceController::class, 'approveBreakGlass'])->name('break-glass.approve');
    Route::post('/dsr/{dataSubjectRequest}/status', [AdminComplianceController::class, 'updateDsrStatus'])->name('dsr.update');
    Route::post('/dsr/{dataSubjectRequest}/process', [AdminComplianceController::class, 'processDsr'])->name('dsr.process');
});
