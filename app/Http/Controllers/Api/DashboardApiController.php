<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DashboardOverviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function __construct(private readonly DashboardOverviewService $dashboardOverviewService) {}

    public function overview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        /** @var User $user */
        $user = $request->user();

        /** @var Tenant $tenant */
        $tenant = app('tenant');

        return response()->json($this->dashboardOverviewService->build(
            $user,
            $tenant,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        ));
    }

    public function stats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'range_type' => ['nullable', 'string', 'in:date,week,month,year'],
            'range_value' => ['nullable', 'string', 'max:20'],
        ]);

        /** @var User $user */
        $user = $request->user();

        /** @var Tenant $tenant */
        $tenant = app('tenant');

        return response()->json($this->dashboardOverviewService->buildStats(
            $user,
            $tenant,
            $validated['range_type'] ?? 'date',
            $validated['range_value'] ?? null,
        ));
    }
}
