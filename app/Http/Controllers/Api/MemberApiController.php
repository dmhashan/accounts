<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BiometricAccessEvent;
use App\Models\Member;
use App\Models\MemberAttendance;
use App\Models\Tenant;
use App\Services\MediaStorageService;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberApiController extends Controller
{
    public function __construct(
        private readonly MemberService $memberService,
        private readonly MediaStorageService $media,
    ) {}

    public function meta(): JsonResponse
    {
        return response()->json($this->memberService->meta());
    }

    public function index(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $currentUser = $request->user();
        $perPage = min((int) $request->integer('per_page', 15), 50);
        $search = trim((string) $request->query('search', ''));
        $isTemp = $request->has('is_temp') ? filter_var($request->query('is_temp'), FILTER_VALIDATE_BOOLEAN) : null;
        $planId = $request->has('plan_id') ? (int) $request->query('plan_id') : null;

        return response()->json($this->memberService->index($tenant->id, $currentUser, $perPage, $search, $isTemp, $planId));
    }

    public function exportGoogleContacts(): StreamedResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        return $this->memberService->exportGoogleContacts($tenant);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('members')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
                Rule::unique('users')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'gender' => ['required', 'in:male,female'],
            'email' => [
                'required',
                'email',
                Rule::unique('members')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
                Rule::unique('users')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'phone_number' => ['required', 'string', 'max:20'],
            'allow_sms' => ['boolean'],
            'allow_whatsapp' => ['boolean'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'nic' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'address' => ['nullable', 'string', 'max:1000'],
            'admission_fee' => ['nullable', 'numeric', 'min:0'],
            'payment_plan_id' => ['required', 'integer', 'exists:payment_plans,id'],
            'joined_date' => ['required', 'date'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $member = $this->memberService->store($tenant, $validated);

        return response()->json([
            'message' => 'Member created successfully.',
            'data' => ['id' => $member->id],
        ], 201);
    }

    public function storeTemp(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'email' => [
                'nullable',
                'email',
                Rule::unique('members')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
        ]);

        $firstName = trim($validated['first_name'] ?? '');
        $lastName = trim($validated['last_name'] ?? '');

        if ($firstName === '' && $lastName === '') {
            return response()->json([
                'message' => 'Either first name or last name is required.',
                'errors' => ['first_name' => ['Either first name or last name is required.']],
            ], 422);
        }

        $member = $this->memberService->storeTemp($tenant, $validated);

        return response()->json([
            'message' => 'Temporary member created successfully.',
            'data' => ['id' => $member->id],
        ], 201);
    }

    public function show(Request $request, Member $member): JsonResponse
    {
        $this->memberService->ensureTenantMember($member, app('tenant')->id);

        return response()->json([
            'data' => $this->memberService->show($member),
            'permissions' => [
                'edit' => $request->user()->hasPermission('users.edit'),
                'delete' => $request->user()->hasPermission('users.delete'),
            ],
        ]);
    }

    public function update(Request $request, Member $member): JsonResponse
    {
        $this->memberService->ensureTenantMember($member, app('tenant')->id);

        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $memberUsernameRule = Rule::unique('members')
            ->where(fn ($query) => $query->where('tenant_id', $tenant->id))
            ->ignore($member->id);
        $memberEmailRule = Rule::unique('members')
            ->where(fn ($query) => $query->where('tenant_id', $tenant->id))
            ->ignore($member->id);

        $userUsernameRule = Rule::unique('users')->where(fn ($query) => $query->where('tenant_id', $tenant->id));
        $userEmailRule = Rule::unique('users')->where(fn ($query) => $query->where('tenant_id', $tenant->id));

        if ($member->user_id) {
            $userUsernameRule = $userUsernameRule->ignore($member->user_id);
            $userEmailRule = $userEmailRule->ignore($member->user_id);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', $memberUsernameRule, $userUsernameRule],
            'gender' => ['required', 'in:male,female'],
            'email' => ['required', 'email', $memberEmailRule, $userEmailRule],
            'phone_number' => ['required', 'string', 'max:20'],
            'allow_sms' => ['boolean'],
            'allow_whatsapp' => ['boolean'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'nic' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'address' => ['nullable', 'string', 'max:1000'],
            'admission_fee' => ['nullable', 'numeric', 'min:0'],
            'payment_plan_id' => ['required', 'integer', 'exists:payment_plans,id'],
            'joined_date' => ['required', 'date'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->memberService->update($member, $validated);

        return response()->json([
            'message' => 'Member updated successfully.',
        ]);
    }

    public function toggleStatus(Member $member): JsonResponse
    {
        $this->memberService->ensureTenantMember($member, app('tenant')->id);

        return response()->json($this->memberService->toggleStatus($member));
    }

    public function toggleVerification(Member $member): JsonResponse
    {
        $this->memberService->ensureTenantMember($member, app('tenant')->id);

        return response()->json($this->memberService->toggleVerification($member));
    }

    public function destroy(Member $member): JsonResponse
    {
        $this->memberService->ensureTenantMember($member, app('tenant')->id);
        $this->memberService->destroy($member);

        return response()->json([
            'message' => 'Member deleted successfully.',
        ]);
    }

    public function uploadAvatar(Request $request, Member $member): JsonResponse
    {
        $this->memberService->ensureTenantMember($member, app('tenant')->id);

        $request->validate([
            'avatar' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $url = $this->memberService->uploadAvatar($member, $request->file('avatar'));

        return response()->json([
            'message' => 'Avatar uploaded successfully.',
            'profile_photo_url' => $url,
        ]);
    }

    public function deleteAvatar(Member $member): JsonResponse
    {
        $this->memberService->ensureTenantMember($member, app('tenant')->id);
        $this->memberService->deleteAvatar($member);

        return response()->json([
            'message' => 'Avatar removed successfully.',
        ]);
    }

    public function attendance(Request $request, Member $member): JsonResponse
    {
        $tenant = app('tenant');
        $this->memberService->ensureTenantMember($member, $tenant->id);

        $year = $request->integer('year', now()->year);

        $records = MemberAttendance::where('tenant_id', $tenant->id)
            ->where(function ($q) use ($member) {
                $q->where('member_id', $member->id)
                    ->orWhere(function ($q2) use ($member) {
                        if ($member->username) {
                            $q2->whereNull('member_id')->where('username', $member->username);
                        } else {
                            $q2->whereRaw('0=1');
                        }
                    });
            })
            ->whereYear('attended_date', $year)
            ->orderBy('attended_date')
            ->get(['id', 'attended_date']);

        $eventsByDate = BiometricAccessEvent::where('tenant_id', $tenant->id)
            ->where('result', 'success')
            ->whereYear('event_time', $year)
            ->where(function ($q) use ($member) {
                $q->where('member_id', $member->id);

                if ($member->biometric_member_id) {
                    $q->orWhere('biometric_member_id', $member->biometric_member_id);
                }
            })
            ->orderBy('event_time')
            ->get(['id', 'event_time', 'picture_path'])
            ->groupBy(function (BiometricAccessEvent $event) {
                return optional($event->event_time)->toDateString();
            })
            ->map(fn ($events) => $events->first());

        $records = $records->map(function (MemberAttendance $attendance) use ($eventsByDate) {
            $date = optional($attendance->attended_date)->toDateString();
            $event = $date ? $eventsByDate->get($date) : null;

            return [
                'id' => $attendance->id,
                'attended_date' => optional($attendance->attended_date)->toDateString(),
                'biometric_access_event_id' => $event?->id,
                'biometric_access_event_link' => $event?->id ? '/#/settings/biometric?event_id=' . $event->id : null,
                'biometric_access_event_time' => optional($event?->event_time)?->toIso8601String(),
                'biometric_access_event_has_picture' => (bool) ($event?->picture_path),
                'biometric_access_event_picture_url' => $event?->picture_path ? $this->media->url($event->picture_path) : null,
            ];
        })->values();

        return response()->json([
            'data' => $records,
            'total' => $records->count(),
            'year' => $year,
        ]);
    }
}
