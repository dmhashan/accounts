<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendRealProfitReportJob;
use App\Models\DailySummaryReport;
use App\Services\DailySummaryReportService;
use App\Services\DailySummaryService;
use App\Services\RealProfitReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportApiController extends Controller
{
    public function __construct(
        private readonly DailySummaryService $dailySummary,
        private readonly DailySummaryReportService $reportService,
        private readonly RealProfitReportService $realProfitReport,
    ) {}

    public function overview(): JsonResponse
    {
        return response()->json([
            'status' => 'coming-soon',
            'features' => [
                ['title' => 'User Activity', 'description' => 'Track user engagement and activity patterns'],
                ['title' => 'Permission Reports', 'description' => 'Analyze role and permission usage'],
                ['title' => 'Performance Metrics', 'description' => 'Monitor system performance and trends'],
                ['title' => 'Audit Logs', 'description' => 'Review system activity and changes'],
            ],
        ]);
    }

    public function dailySummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return response()->json(
            $this->dailySummary->build(app('tenant')->id, $validated['date'] ?? null),
        );
    }

    public function realProfit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        return response()->json(
            $this->realProfitReport->build(app('tenant')->id, $validated['month'] ?? null),
        );
    }

    public function downloadRealProfitPdf(Request $request): Response
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $pdf = $this->realProfitReport->pdf(app('tenant')->id, $validated['month'] ?? null, app('tenant'));

        return response($pdf['contents'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $pdf['filename'] . '"',
        ]);
    }

    public function emailRealProfit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        SendRealProfitReportJob::dispatch(app('tenant')->id, $validated['month'] ?? null);

        return response()->json([
            'message' => 'Real profit report email queued for administrators.',
        ], 202);
    }

    // ── Signed report generation ────────────────────────────────────

    public function generateDailySummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'prepared_by_name' => ['required', 'string', 'max:120'],
            'signature' => ['required', 'string', 'regex:/^data:image\/(png|jpe?g|webp);base64,/i'],
            'selfie' => ['required', 'string', 'regex:/^data:image\/(png|jpe?g|webp);base64,/i'],
            'accounts' => ['array'],
            'accounts.*.id' => ['required', 'integer'],
            'accounts.*.opening_balance' => ['nullable', 'numeric'],
            'accounts.*.income' => ['nullable', 'numeric'],
            'accounts.*.expense' => ['nullable', 'numeric'],
            'stock' => ['array'],
            'stock.*.product_id' => ['required', 'integer'],
            'stock.*.opening' => ['nullable', 'numeric'],
            'stock.*.received' => ['nullable', 'numeric'],
            'stock.*.sold' => ['nullable', 'numeric'],
        ]);

        $report = $this->reportService->generate(
            app('tenant')->id,
            $validated,
            $request->user()?->id,
        );

        return response()->json([
            'message' => 'Daily summary report generated and emailed to administrators.',
            'report' => $this->reportService->show($report),
        ], 201);
    }

    public function dailySummaryHistory(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        return response()->json(
            $this->reportService->history(app('tenant')->id, $perPage),
        );
    }

    public function showDailySummaryReport(DailySummaryReport $report): JsonResponse
    {
        $this->guardReport($report);

        return response()->json($this->reportService->show($report));
    }

    public function downloadDailySummaryReport(DailySummaryReport $report): Response
    {
        $this->guardReport($report);

        $contents = $this->reportService->pdfContents($report);

        abort_if($contents === null, 404, 'PDF not available.');

        $filename = basename((string) $report->pdf_path);

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function guardReport(DailySummaryReport $report): void {}
}
