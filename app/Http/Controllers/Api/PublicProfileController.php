<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberActivityLog;
use App\Models\Sale;
use App\Models\WorkoutProgramAssignment;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicProfileController extends Controller
{
    /**
     * Send an OTP to the member's registered mobile number.
     */
    public function requestOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:30',
        ]);

        $tenant = app('tenant');
        $phone  = trim($request->phone_number);

        $member = Member::where('tenant_id', $tenant->id)
            ->where('phone_number', $phone)
            ->where('is_active', true)
            ->first();

        if (! $member) {
            return response()->json([
                'message' => 'No active member found with this mobile number.',
            ], 422);
        }

        $otp      = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $cacheKey = "otp:{$tenant->id}:{$phone}";

        Cache::put($cacheKey, $otp, now()->addMinutes(10));

        app(SmsService::class)->send($phone, "Your {$tenant->name} verification code is: {$otp}");

        return response()->json(['message' => 'OTP sent successfully.']);
    }

    /**
     * Verify the submitted OTP and return the member ID on success.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:30',
            'otp'          => 'required|string|size:6',
        ]);

        $tenant   = app('tenant');
        $phone    = trim($request->phone_number);
        $cacheKey = "otp:{$tenant->id}:{$phone}";
        $stored   = Cache::get($cacheKey);

        if (! $stored || $stored !== $request->otp) {
            return response()->json([
                'message' => 'Invalid or expired OTP. Please try again.',
            ], 422);
        }

        $member = Member::where('tenant_id', $tenant->id)
            ->where('phone_number', $phone)
            ->where('is_active', true)
            ->first();

        if (! $member) {
            return response()->json(['message' => 'Member not found.'], 422);
        }

        Cache::forget($cacheKey);

        $token = (string) \Illuminate\Support\Str::uuid();
        Cache::put("pp_token:{$token}", [
            'member_id' => $member->id,
            'tenant_id' => $tenant->id,
        ], now()->addMonths(3));

        return response()->json(['token' => $token]);
    }

    /**
     * Return the full public profile data for a given member ID.
     */
    public function getProfile(Request $request)
    {
        $tenant   = app('tenant');
        $memberId = $request->input('_pp_member_id');

        $member = Member::where('tenant_id', $tenant->id)
            ->where('id', $memberId)
            ->where('is_active', true)
            ->firstOrFail();

        // Assigned workout plans
        $assignedWorkouts = WorkoutProgramAssignment::with([
            'assignedProgram.creator',
            'assignedProgram.days.dayExercises.exercise',
            'assignedProgram.extras',
        ])
            ->where('tenant_id', $tenant->id)
            ->where('member_id', $member->id)
            ->orderByDesc('effective_date')
            ->get();

        // Sales / invoices
        $sales            = Sale::where('tenant_id', $tenant->id)
            ->where('customer_member_id', $member->id)
            ->orderByDesc('created_at')
            ->with(['items.product', 'items.variation'])
            ->get();
        $totalOutstanding = $sales->where('is_paid', false)->sum('balance');

        // Format workouts
        $workoutsData = $assignedWorkouts->map(function ($assignment) {
            $program = $assignment->assignedProgram;

            return [
                'title'          => $program->title ?? 'N/A',
                'duration_weeks' => $program->duration_weeks,
                'creator_name'   => $program->creator->name ?? null,
                'effective_date' => $assignment->effective_date?->format('Y-m-d'),
                'days'           => ($program->days ?? collect())->map(function ($day) {
                    return [
                        'day_number' => $day->day_number,
                        'title'      => $day->title,
                        'exercises'  => $day->dayExercises->map(fn ($ex) => [
                            'exercise_name'  => $ex->exercise->name ?? 'Exercise',
                            'w1_w3_exercise' => $ex->w1_w3_exercise,
                            'w2_w4_exercise' => $ex->w2_w4_exercise,
                            'sets'           => $ex->sets,
                            'reps'           => $ex->reps,
                            'tempo'          => $ex->tempo,
                            'rest_seconds'   => $ex->rest_seconds,
                        ])->values(),
                    ];
                })->values(),
                'extras' => ($program->extras ?? collect())->map(fn ($e) => [
                    'type'               => $e->type,
                    'exercise_name'      => $e->exercise_name,
                    'sets'               => $e->sets,
                    'reps_or_time'       => $e->reps_or_time,
                    'rest'               => $e->rest,
                    'notes'              => $e->notes,
                    'frequency_per_week' => $e->frequency_per_week,
                    'duration_minutes'   => $e->duration_minutes,
                    'cardio_type'        => $e->cardio_type,
                ])->values(),
            ];
        })->values();

        // Format sales
        $salesData = $sales->map(fn ($sale) => [
            'id'               => $sale->id,
            'created_at'       => $sale->created_at->format('Y-m-d'),
            'customer_name'    => $sale->customer_name,
            'customer_type'    => $sale->customer_type,
            'payment_method'   => $sale->payment_method,
            'reference_number' => $sale->reference_number,
            'total_amount'     => number_format($sale->total_amount, 2),
            'paid_amount'      => number_format($sale->paid_amount, 2),
            'balance'          => number_format($sale->balance, 2),
            'is_paid'          => $sale->is_paid,
            'items'            => $sale->items->map(fn ($item) => [
                'product_name'   => $item->product->name ?? '-',
                'variation_name' => $item->variation->name ?? null,
                'quantity'       => $item->quantity,
                'unit_price'     => number_format($item->unit_price, 2),
                'subtotal'       => number_format($item->subtotal, 2),
            ])->values(),
        ])->values();

        return response()->json([
            'meta' => [
                'name'              => $member->name,
                'username'          => $member->username,
                'gender'            => $member->gender,
                'joined_date'       => $member->joined_date?->format('Y-m-d'),
                'member_role'       => $member->member_role,
                'email'             => $member->email,
                'phone_number'      => $member->phone_number,
                'tenant_name'       => $tenant->name,
                'total_outstanding' => number_format($totalOutstanding, 2),
            ],
            'workouts' => $workoutsData,
            'sales'    => $salesData,
        ]);
    }

    /**
     * Record a public-profile activity event (fire-and-forget).
     * No member authentication required — tenant context is enough.
     */
    public function logActivity(Request $request)
    {
        $request->validate([
            'session_id'    => 'required|string|max:64',
            'event_type'    => 'required|string|max:50',
            'member_id'     => 'nullable|integer',
            'section'       => 'nullable|string|max:100',
            'screen_width'  => 'nullable|integer|min:1|max:9999',
            'screen_height' => 'nullable|integer|min:1|max:9999',
            'metadata'      => 'nullable|array',
        ]);

        $tenant = app('tenant');
        $ua     = $request->userAgent() ?? '';
        $parsed = MemberActivityLog::parseUserAgent($ua);

        // Validate member_id belongs to this tenant before storing
        $memberId = null;
        if ($request->filled('member_id')) {
            $exists   = Member::where('tenant_id', $tenant->id)->where('id', $request->member_id)->exists();
            $memberId = $exists ? (int) $request->member_id : null;
        }

        MemberActivityLog::create([
            'tenant_id'     => $tenant->id,
            'member_id'     => $memberId,
            'session_id'    => $request->session_id,
            'event_type'    => $request->event_type,
            'section'       => $request->section,
            'ip_address'    => $request->ip(),
            'user_agent'    => $ua,
            'device_type'   => $parsed['device_type'],
            'browser'       => $parsed['browser'],
            'os'            => $parsed['os'],
            'screen_width'  => $request->screen_width,
            'screen_height' => $request->screen_height,
            'metadata'      => $request->metadata,
        ]);

        return response()->json(['ok' => true]);
    }
}
