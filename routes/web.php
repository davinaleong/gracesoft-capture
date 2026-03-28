<?php

use App\Http\Controllers\FormManagementController;
use App\Http\Controllers\EnquiryNoteController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\PublicFormController;
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

Route::prefix('manage/forms')->name('manage.forms.')->group(function () {
    Route::get('/', [FormManagementController::class, 'index'])->name('index');
    Route::get('/create', [FormManagementController::class, 'create'])->name('create');
    Route::post('/', [FormManagementController::class, 'store'])->name('store');
    Route::get('/{form}/edit', [FormManagementController::class, 'edit'])->name('edit');
    Route::put('/{form}', [FormManagementController::class, 'update'])->name('update');
    Route::post('/{form}/toggle-active', [FormManagementController::class, 'toggleActive'])->name('toggle-active');
});

Route::prefix('inbox')->name('inbox.')->group(function () {
    Route::get('/', [InboxController::class, 'index'])->name('index');
    Route::get('/{enquiry}', [InboxController::class, 'show'])->name('show');
    Route::post('/{enquiry}/status', [InboxController::class, 'updateStatus'])->name('status.update');
    Route::post('/{enquiry}/notes', [EnquiryNoteController::class, 'store'])->name('notes.store');
});
