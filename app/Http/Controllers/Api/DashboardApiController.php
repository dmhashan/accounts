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
    public function __construct(private readonly DashboardOverviewService $dashboardOverviewService)
    {
    }

    public function overview(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Tenant $tenant */
        $tenant = app('tenant');

        return response()->json($this->dashboardOverviewService->build($user, $tenant));
    }
}
