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
        if ($request->has('account_ids') && is_string($request->input('account_ids'))) {
            $request->merge([
                'account_ids' => array_filter(array_map('intval', explode(',', $request->input('account_ids')))),
            ]);
        }

        $validated = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'account_ids' => ['nullable', 'array'],
            'account_ids.*' => ['integer', 'exists:company_accounts,id'],
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
            $validated['account_ids'] ?? [],
        ));
    }

    public function stats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'range_type' => ['nullable', 'string', 'in:date,week,month,year,date_range'],
            'range_value' => ['nullable', 'string', 'max:20'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
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
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        ));
    }
}
