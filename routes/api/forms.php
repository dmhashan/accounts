<?php

use App\Http\Controllers\Api\FormBuilderApiController;
use App\Models\Member;
use Illuminate\Support\Facades\Route;

// Form templates management (requires forms.manage)
Route::middleware(['auth', 'permission:forms.manage'])->group(function () {
    Route::get('/forms/templates', [FormBuilderApiController::class, 'indexTemplates']);
    Route::get('/forms/templates/active', [FormBuilderApiController::class, 'activeTemplates']);
    Route::post('/forms/templates', [FormBuilderApiController::class, 'storeTemplate']);
    Route::get('/forms/templates/{template}', [FormBuilderApiController::class, 'showTemplate']);
    Route::put('/forms/templates/{template}', [FormBuilderApiController::class, 'updateTemplate']);
    Route::delete('/forms/templates/{template}', [FormBuilderApiController::class, 'destroyTemplate']);

    // Submissions management
    Route::get('/forms/templates/{template}/submissions', [FormBuilderApiController::class, 'indexSubmissions']);
    Route::get('/forms/submissions/{submission}', [FormBuilderApiController::class, 'showSubmission']);
    Route::get('/forms/submissions/{submission}/pdf-url', [FormBuilderApiController::class, 'submissionPdfUrl']);
    Route::delete('/forms/submissions/{submission}', [FormBuilderApiController::class, 'destroySubmission']);
});

// Submit a form for a member (staff filling on behalf)
Route::middleware(['auth', 'permission:members.edit,users.edit'])->group(function () {
    Route::post('/forms/templates/{template}/members/{member}/submit', [FormBuilderApiController::class, 'submitForm']);
    Route::get('/members/{member}/form-submissions', [FormBuilderApiController::class, 'memberSubmissions']);
});
