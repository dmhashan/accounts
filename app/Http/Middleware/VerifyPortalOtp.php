<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class VerifyPortalOtp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, \Closure $next): Response
    {
        // Only require OTP verification for mutating methods
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        // Exclude authentication actions (login, request login OTP, logout) from action-OTP checks
        if ($request->is('api/portal/auth/*') && !$request->is('api/portal/auth/action-otp')) {
            return $next($request);
        }

        $user = auth('portal')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $otpCode = $request->header('X-Portal-OTP') ?: $request->input('otp_code');

        if (!$otpCode) {
            return response()->json([
                'message' => 'OTP verification is required to perform this action.',
                'otp_required' => true,
            ], 422);
        }

        $cacheKey = "otp:portal:action:{$user->id}";
        $storedOtp = Cache::get($cacheKey);

        if (!$storedOtp || $storedOtp !== trim($otpCode)) {
            return response()->json([
                'message' => 'Invalid or expired OTP. Please try again.',
                'otp_required' => true,
            ], 422);
        }

        // OTP verified successfully. Consume/forget the OTP and proceed.
        Cache::forget($cacheKey);

        return $next($request);
    }
}
