<?php

use App\Http\Controllers\PublicFormController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/form/{token}', [PublicFormController::class, 'show'])
    ->name('forms.show');

Route::post('/form/{token}/submit', [PublicFormController::class, 'submit'])
    ->middleware('throttle:form-submissions')
    ->name('forms.submit');
