<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AppContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppContextApiController extends Controller
{
    public function __construct(private readonly AppContextService $appContextService)
    {
    }

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Tenant $tenant */
        $tenant = app('tenant');

        return response()->json($this->appContextService->build($user, $tenant));
    }
}
