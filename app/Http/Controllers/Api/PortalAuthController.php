<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PortalAuthController extends Controller
{
    public function __construct(
        private readonly SmsService $smsService,
    ) {}

    /**
     * Request an OTP for portal login.
     */
    public function requestLoginOtp(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string|max:255',
        ]);

        $identifier = trim($request->identifier);

        // Find by email or mobile number
        $user = PortalUser::where('is_active', true)
            ->where(function ($query) use ($identifier) {
                $query->where('email', $identifier)
                    ->orWhere('mobile_number', $identifier);
            })
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'No active portal administrator found with this email or mobile number.',
            ], 422);
        }

        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $cacheKey = "otp:portal:login:{$user->id}";

        Cache::put($cacheKey, $otp, now()->addMinutes(10));

        $this->smsService->send($user->mobile_number, "Your Portal login verification code is: {$otp}.");

        $response = ['message' => 'Verification code sent to registered mobile number.'];

        if (app()->environment('local', 'testing')) {
            $response['otp_debug'] = $otp;
        }

        return response()->json($response);
    }

    /**
     * Authenticate and log in the user using the OTP.
     */
    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string|max:255',
            'otp' => 'required|string|size:6',
        ]);

        $identifier = trim($request->identifier);
        $otp = trim($request->otp);

        $user = PortalUser::where('is_active', true)
            ->where(function ($query) use ($identifier) {
                $query->where('email', $identifier)
                    ->orWhere('mobile_number', $identifier);
            })
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Authentication failed. User not found.',
            ], 422);
        }

        $cacheKey = "otp:portal:login:{$user->id}";
        $storedOtp = Cache::get($cacheKey);

        if (!$storedOtp || $storedOtp !== $otp) {
            return response()->json([
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        Cache::forget($cacheKey);

        // Perform login via 'portal' session guard
        auth('portal')->login($user);
        $request->session()->regenerate();

        return response()->json([
            'user' => $user,
            'message' => 'Logged in successfully.',
        ]);
    }

    /**
     * Check current authentication status and return user details.
     */
    public function me(Request $request)
    {
        $user = auth('portal')->user();

        if (!$user) {
            return response()->json([
                'authenticated' => false,
            ], 401);
        }

        return response()->json([
            'authenticated' => true,
            'user' => $user,
        ]);
    }

    /**
     * Log out the authenticated user.
     */
    public function logout(Request $request)
    {
        auth('portal')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Request an OTP for a mutating action/edit.
     */
    public function requestActionOtp(Request $request)
    {
        $user = auth('portal')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $cacheKey = "otp:portal:action:{$user->id}";

        Cache::put($cacheKey, $otp, now()->addMinutes(10));

        $this->smsService->send($user->mobile_number, "Your Portal action verification code is: {$otp}.");

        $response = ['message' => 'Verification code sent to registered mobile number.'];

        if (app()->environment('local', 'testing')) {
            $response['otp_debug'] = $otp;
        }

        return response()->json($response);
    }
}
