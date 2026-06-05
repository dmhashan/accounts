<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DailySummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportApiController extends Controller
{
    public function __construct(
        private readonly DailySummaryService $dailySummary,
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
}
