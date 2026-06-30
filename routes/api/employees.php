<?php

use App\Http\Controllers\Api\EmployeeApiController;
use App\Http\Controllers\Api\EmployeePaySheetApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:employees.manage'])->group(function () {
    Route::get('/employees/meta', [EmployeeApiController::class, 'meta']);
    Route::get('/employees', [EmployeeApiController::class, 'index']);
    Route::post('/employees', [EmployeeApiController::class, 'store']);
    Route::get('/employees/{employee}', [EmployeeApiController::class, 'show']);
    Route::put('/employees/{employee}', [EmployeeApiController::class, 'update']);
    Route::delete('/employees/{employee}', [EmployeeApiController::class, 'destroy']);

    Route::get('/employees/{employee}/documents', [EmployeeApiController::class, 'documents']);
    Route::get('/employees/{employee}/documents/{document}/url', [EmployeeApiController::class, 'documentUrl']);
    Route::post('/employees/{employee}/documents', [EmployeeApiController::class, 'storeDocument']);
    Route::delete('/employees/{employee}/documents/{document}', [EmployeeApiController::class, 'destroyDocument']);

    Route::get('/employees/{employee}/attendance', [EmployeeApiController::class, 'attendance']);
    Route::post('/employees/{employee}/attendance', [EmployeeApiController::class, 'storeAttendance']);
    Route::delete('/employees/{employee}/attendance/{attendance}', [EmployeeApiController::class, 'destroyAttendance']);
});

Route::middleware(['auth', 'permission:employee_pay_sheets.manage'])->group(function () {
    Route::get('/employees/{employee}/pay-sheets', [EmployeePaySheetApiController::class, 'employee']);
    Route::post('/employees/{employee}/pay-sheets/generate', [EmployeePaySheetApiController::class, 'generateEmployee']);
    Route::get('/employees/{employee}/pay-sheet-adjustments', [EmployeePaySheetApiController::class, 'employeeAdjustments']);
    Route::post('/employees/{employee}/pay-sheet-adjustments', [EmployeePaySheetApiController::class, 'storeEmployeeAdjustment']);
    Route::put('/employees/{employee}/pay-sheet-adjustments/{employeePaySheetAdjustment}', [EmployeePaySheetApiController::class, 'updateEmployeeAdjustment']);
    Route::delete('/employees/{employee}/pay-sheet-adjustments/{employeePaySheetAdjustment}', [EmployeePaySheetApiController::class, 'destroyEmployeeAdjustment']);
    Route::get('/employees/{employee}/pay-sheets/{employeePaySheetItem}/pdf', [EmployeePaySheetApiController::class, 'employeeItemPdf']);
    Route::get('/employees/{employee}/pay-sheets/{employeePaySheetItem}', [EmployeePaySheetApiController::class, 'employeeItem']);
    Route::get('/employee-pay-sheets/meta', [EmployeePaySheetApiController::class, 'meta']);
    Route::get('/employee-pay-sheets', [EmployeePaySheetApiController::class, 'index']);
    Route::post('/employee-pay-sheets/generate', [EmployeePaySheetApiController::class, 'generate']);
    Route::get('/employee-pay-sheets/{employeePaySheetRun}', [EmployeePaySheetApiController::class, 'show']);
    Route::post('/employee-pay-sheets/{employeePaySheetRun}/mark-paid', [EmployeePaySheetApiController::class, 'markPaid']);
    Route::delete('/employee-pay-sheets/{employeePaySheetRun}', [EmployeePaySheetApiController::class, 'destroy']);
});
