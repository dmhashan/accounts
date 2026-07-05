<?php

use App\Http\Controllers\Api\ReportApiController;
use App\Http\Controllers\Api\Reports\MemberAnalysisReportController;
use Illuminate\Support\Facades\Route;

Route::get('/reports/overview', [ReportApiController::class, 'overview'])->middleware(['auth', 'permission:reports.view,reports.daily_summary,reports.real_profit,reports.statistics,reports.member_analysis,reports.customers,reports.products']);
Route::get('/reports/real-profit', [ReportApiController::class, 'realProfit'])->middleware(['auth', 'permission:reports.real_profit,reports.view']);
Route::get('/reports/real-profit/pdf', [ReportApiController::class, 'downloadRealProfitPdf'])->middleware(['auth', 'permission:reports.real_profit,reports.view']);
Route::post('/reports/real-profit/email', [ReportApiController::class, 'emailRealProfit'])->middleware(['auth', 'permission:reports.real_profit,reports.view']);
Route::get('/reports/daily-summary', [ReportApiController::class, 'dailySummary'])->middleware(['auth', 'permission:reports.daily_summary,reports.view']);
Route::post('/reports/daily-summary/generate', [ReportApiController::class, 'generateDailySummary'])->middleware(['auth', 'permission:reports.daily_summary,reports.view']);
Route::get('/reports/daily-summary/history', [ReportApiController::class, 'dailySummaryHistory'])->middleware(['auth', 'permission:reports.daily_summary,reports.view']);
Route::get('/reports/daily-summary/reports/{report}', [ReportApiController::class, 'showDailySummaryReport'])->middleware(['auth', 'permission:reports.daily_summary,reports.view']);
Route::get('/reports/daily-summary/reports/{report}/pdf', [ReportApiController::class, 'downloadDailySummaryReport'])->middleware(['auth', 'permission:reports.daily_summary,reports.view']);

Route::prefix('/reports/member-analysis')
    ->middleware(['auth', 'permission:reports.member_analysis,reports.view'])
    ->group(function () {
        Route::get('/summary', [MemberAnalysisReportController::class, 'summary']);
        Route::get('/members', [MemberAnalysisReportController::class, 'members']);
        Route::patch('/members/status', [MemberAnalysisReportController::class, 'updateMemberStatus'])->middleware('permission:members.edit,users.edit');
        Route::get('/export', [MemberAnalysisReportController::class, 'export']);
        Route::get('/filters/options', [MemberAnalysisReportController::class, 'filterOptions']);
    });
