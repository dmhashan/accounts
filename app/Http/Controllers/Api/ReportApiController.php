<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailySummaryReport;
use App\Services\DailySummaryReportService;
use App\Services\DailySummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportApiController extends Controller
{
    public function __construct(
        private readonly DailySummaryService $dailySummary,
        private readonly DailySummaryReportService $reportService,
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
