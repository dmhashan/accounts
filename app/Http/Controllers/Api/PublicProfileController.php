<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BulkNotification;
use App\Models\Member;
use App\Models\MemberActivityLog;
use App\Models\Sale;
use App\Models\WorkoutProgramAssignment;
use App\Services\EventService;
use App\Services\MediaStorageService;
use App\Services\MemberPortalUrlService;
use App\Services\SmsService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicProfileController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly MediaStorageService $media,
        private readonly MemberPortalUrlService $memberPortalUrl,
    ) {}

    /**
     * Send an OTP to the member's registered mobile number.
     */
    public function requestOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:30',
        ]);

        $tenant = app('tenant');
        $phone = trim($request->phone_number);

        $member = Member::where('tenant_id', $tenant->id)
            ->where('phone_number', $phone)
            ->where('is_active', true)
            ->first();

        if (!$member) {
            return response()->json([
                'message' => 'No active member found with this mobile number.',
            ], 422);
        }

        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $cacheKey = "otp:{$tenant->tenant_uuid}:{$phone}";

        Cache::put($cacheKey, $otp, now()->addMinutes(10));

        $profileUrl = $this->memberPortalUrl->urlForTenant($tenant);
        app(SmsService::class)->send($phone, "Your {$tenant->name} verification code is: {$otp}. Access your profile: {$profileUrl}");

        return response()->json(['message' => 'OTP sent successfully.']);
    }

    /**
     * Verify the submitted OTP and return the member ID on success.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:30',
            'otp' => 'required|string|size:6',
        ]);

        $tenant = app('tenant');
        $phone = trim($request->phone_number);
        $cacheKey = "otp:{$tenant->tenant_uuid}:{$phone}";
        $stored = Cache::get($cacheKey);

        if (!$stored || $stored !== $request->otp) {
            return response()->json([
                'message' => 'Invalid or expired OTP. Please try again.',
            ], 422);
        }

        $member = Member::where('tenant_id', $tenant->id)
            ->where('phone_number', $phone)
            ->where('is_active', true)
            ->first();

        if (!$member) {
            return response()->json(['message' => 'Member not found.'], 422);
        }

        Cache::forget($cacheKey);

        $token = (string) \Illuminate\Support\Str::uuid();
        Cache::put("pp_token:{$token}", [
            'member_id' => $member->id,
            'tenant_uuid' => $tenant->tenant_uuid,
        ], now()->addMonths(3));

        return response()->json(['token' => $token]);
    }

    /**
     * Return the full public profile data for a given member ID.
     */
    public function getProfile(Request $request)
    {
        $tenant = app('tenant');
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
        $sales = Sale::where('tenant_id', $tenant->id)
            ->where('customer_member_id', $member->id)
            ->orderByDesc('created_at')
            ->with(['items.product', 'items.variation'])
            ->get();
        $totalOutstanding = $sales->where('is_paid', false)->sum('balance');

        // Format workouts
        $workoutsData = $assignedWorkouts->map(function ($assignment) {
            $program = $assignment->assignedProgram;

            return [
                'title' => $program->title ?? 'N/A',
                'duration_weeks' => $program->duration_weeks,
                'creator_name' => $program->creator->name ?? null,
                'effective_date' => $assignment->effective_date?->format('Y-m-d'),
                'days' => ($program->days ?? collect())->map(function ($day) {
                    return [
                        'day_number' => $day->day_number,
                        'title' => $day->title,
                        'exercises' => $day->dayExercises->map(fn ($ex) => [
                            'exercise_name' => $ex->exercise->name ?? 'Exercise',
                            'w1_w3_exercise' => $ex->w1_w3_exercise,
                            'w2_w4_exercise' => $ex->w2_w4_exercise,
                            'sets' => $ex->sets,
                            'reps' => $ex->reps,
                            'tempo' => $ex->tempo,
                            'rest_seconds' => $ex->rest_seconds,
                        ])->values(),
                    ];
                })->values(),
                'extras' => ($program->extras ?? collect())->map(fn ($e) => [
                    'type' => $e->type,
                    'exercise_name' => $e->exercise_name,
                    'sets' => $e->sets,
                    'reps_or_time' => $e->reps_or_time,
                    'rest' => $e->rest,
                    'notes' => $e->notes,
                    'frequency_per_week' => $e->frequency_per_week,
                    'duration_minutes' => $e->duration_minutes,
                    'cardio_type' => $e->cardio_type,
                ])->values(),
            ];
        })->values();

        // Format sales
        $salesData = $sales->map(fn ($sale) => [
            'id' => $sale->id,
            'created_at' => $sale->created_at->format('Y-m-d'),
            'customer_name' => $sale->customer_name,
            'customer_type' => $sale->customer_type,
            'payment_method' => $sale->payment_method,
            'reference_number' => $sale->reference_number,
            'total_amount' => number_format($sale->total_amount, 2),
            'paid_amount' => number_format($sale->paid_amount, 2),
            'balance' => number_format($sale->balance, 2),
            'is_paid' => $sale->is_paid,
            'items' => $sale->items->map(fn ($item) => [
                'product_name' => $item->product->name ?? '-',
                'variation_name' => $item->variation->name ?? null,
                'quantity' => $item->quantity,
                'unit_price' => number_format($item->unit_price, 2),
                'subtotal' => number_format($item->subtotal, 2),
            ])->values(),
        ])->values();

        // Wallet data — first page for immediate display
        $walletPage = $this->walletService->transactions($member, $tenant->id, 10);

        return response()->json([
            'meta' => [
                'name' => $member->name,
                'username' => $member->username,
                'gender' => $member->gender,
                'joined_date' => $member->joined_date?->format('Y-m-d'),
                'member_role' => $member->member_role,
                'email' => $member->email,
                'phone_number' => $member->phone_number,
                'tenant_name' => $tenant->name,
                'total_outstanding' => number_format($totalOutstanding, 2),
                'current_balance' => round((float) $member->current_balance, 2),
                'profile_photo_url' => $member->profile_photo_path
                    ? $this->media->url($member->profile_photo_path)
                    : null,
            ],
            'workouts' => $workoutsData,
            'sales' => $salesData,
            'wallet_transactions' => $walletPage['data'],
            'wallet_tx_meta' => $walletPage['meta'],
        ]);
    }

    /**
     * Return public event details by slug. No authentication required.
     */
    public function showEvent(Request $request, string $slug)
    {
        $tenant = app('tenant');
        $event = app(EventService::class)->publicEvent($slug, $tenant->id);

        if (!$event) {
            return response()->json(['message' => 'Event not found.'], 404);
        }

        return response()->json($event);
    }

    /**
     * Register for an event. No authentication required; member resolved from optional PP token.
     */
    public function registerEvent(Request $request, string $slug)
    {
        $tenant = app('tenant');

        $event = \App\Models\Event::where('tenant_id', $tenant->id)
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$event) {
            return response()->json(['message' => 'Event not found or registration is closed.'], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'guests' => ['nullable', 'array', 'max:20'],
            'guests.*.name' => ['required', 'string', 'max:200'],
            'guests.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Resolve optional member from PP token header
        $memberId = null;
        $token = $request->header('X-PP-Token');

        if ($token) {
            $cached = Cache::get("pp_token:{$token}");

            if ($cached && ($cached['tenant_uuid'] ?? null) === $tenant->tenant_uuid) {
                $memberId = $cached['member_id'];
            }
        }

        // Block duplicate registrations for logged-in members
        if ($memberId) {
            $existing = app(EventService::class)->getMyRegistration($event, $memberId);

            if ($existing) {
                return response()->json(['message' => 'You have already registered for this event.'], 409);
            }
        }

        $registration = app(EventService::class)->register($event, $tenant->id, $validated, $memberId);

        return response()->json([
            'message' => 'Registration successful.',
            'data' => app(EventService::class)->toRegistrationItem($registration->load('guests')),
        ], 201);
    }

    /**
     * Return paginated wallet transactions for the authenticated member (public portal).
     */
    public function getWalletTransactions(Request $request)
    {
        $tenant = app('tenant');
        $memberId = $request->input('_pp_member_id');
        $perPage = min(max(1, (int) $request->input('per_page', 15)), 50);

        $member = Member::where('tenant_id', $tenant->id)
            ->where('id', $memberId)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json($this->walletService->transactions($member, $tenant->id, $perPage));
    }

    /**
     * Return sent notifications addressed to the authenticated member.
     */
    public function getNotifications(Request $request)
    {
        $tenant = app('tenant');
        $memberId = $request->input('_pp_member_id');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(max(1, (int) $request->input('per_page', 15)), 50);

        $notifications = BulkNotification::where('tenant_id', $tenant->id)
            ->where('status', 'sent')
            ->whereHas('recipients', fn ($q) => $q->where('member_id', $memberId))
            ->orderByDesc('sent_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => collect($notifications->items())->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->name,
                'message' => $n->message,
                'sent_at' => $n->sent_at?->toDateTimeString(),
            ])->values(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Return upcoming active events for public display (no auth required).
     */
    public function getUpcomingEvents(Request $request)
    {
        $tenant = app('tenant');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(max(1, (int) $request->input('per_page', 10)), 50);

        $events = \App\Models\Event::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where('start_datetime', '>', now())
            ->orderBy('start_datetime')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => collect($events->items())->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'slug' => $e->slug,
                'start_datetime' => $e->start_datetime->toDateTimeString(),
                'end_datetime' => $e->end_datetime?->toDateTimeString(),
                'venue' => $e->venue,
                'ticket_fee' => (float) $e->ticket_fee,
            ])->values(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    /**
     * Record a public-profile activity event (fire-and-forget).
     * No member authentication required — tenant context is enough.
     */
    public function logActivity(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string|max:64',
            'event_type' => 'required|string|max:50',
            'screen_width' => 'nullable|integer|min:1|max:9999',
            'screen_height' => 'nullable|integer|min:1|max:9999',
            'metadata' => 'nullable|array',
        ]);

        $tenant = app('tenant');
        $ua = $request->userAgent() ?? '';
        $parsed = MemberActivityLog::parseUserAgent($ua);

        // Resolve member from the PP token header (same logic as ResolvePpToken middleware)
        $memberId = null;
        $token = $request->header('X-PP-Token');

        if ($token) {
            $cached = Cache::get("pp_token:{$token}");

            if ($cached && ($cached['tenant_uuid'] ?? null) === $tenant->tenant_uuid) {
                $memberId = $cached['member_id'];
            }
        }

        MemberActivityLog::create([
            'tenant_id' => $tenant->id,
            'member_id' => $memberId,
            'session_id' => $request->session_id,
            'event_type' => $request->event_type,
            'ip_address' => $request->ip(),
            'user_agent' => $ua,
            'device_type' => $parsed['device_type'],
            'browser' => $parsed['browser'],
            'os' => $parsed['os'],
            'screen_width' => $request->screen_width,
            'screen_height' => $request->screen_height,
            'metadata' => $request->metadata,
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Return the authenticated member's registration for an event, if any.
     */
    public function getMyEventRegistration(Request $request, string $slug)
    {
        $tenant = app('tenant');
        $memberId = $request->input('_pp_member_id');

        $event = \App\Models\Event::where('tenant_id', $tenant->id)
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$event) {
            return response()->json(['message' => 'Event not found.'], 404);
        }

        $registration = app(EventService::class)->getMyRegistration($event, $memberId);

        if (!$registration) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => app(EventService::class)->toRegistrationItem($registration)]);
    }

    /**
     * Update the authenticated member's existing registration for an event.
     */
    public function updateMyEventRegistration(Request $request, string $slug)
    {
        $tenant = app('tenant');
        $memberId = $request->input('_pp_member_id');

        $event = \App\Models\Event::where('tenant_id', $tenant->id)
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$event) {
            return response()->json(['message' => 'Event not found.'], 404);
        }

        $registration = app(EventService::class)->getMyRegistration($event, $memberId);

        if (!$registration) {
            return response()->json(['message' => 'No registration found to update.'], 404);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'guests' => ['nullable', 'array', 'max:20'],
            'guests.*.name' => ['required', 'string', 'max:200'],
            'guests.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $updated = app(EventService::class)->updateRegistration($registration, $event, $validated);

        return response()->json([
            'message' => 'Registration updated.',
            'data' => app(EventService::class)->toRegistrationItem($updated),
        ]);
    }
}
