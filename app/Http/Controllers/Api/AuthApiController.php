<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthApiController extends Controller
{
    public function __construct(private readonly AuthSessionService $authSessionService) {}

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (!$this->authSessionService->attemptTenantLogin(app('tenant')->id, $credentials['login'], $credentials['password'])) {
            return response()->json([
                'message' => 'Invalid username/email or password for this tenant.',
            ], 422);
        }

        $this->authSessionService->regenerateSession($request);

        return response()->json([
            'message' => 'Login successful.',
            'redirect' => '/#/dashboard',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authSessionService->logout($request);

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        if (!$this->authSessionService->refreshSession($request)) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return response()->json([
            'message' => 'Session refreshed successfully.',
        ]);
    }
}
