<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $filters = [
            'active' => $request->query('active'),
            'verified' => $request->query('verified'),
            'gender' => $request->query('gender'),
            'expiry_preset' => $request->query('expiry_preset'),
            'expiry_days_operator' => $request->query('expiry_days_operator'),
            'expiry_days' => $request->query('expiry_days'),
            'attendance_preset' => $request->query('attendance_preset'),
            'attendance_days_operator' => $request->query('attendance_days_operator'),
            'attendance_days' => $request->query('attendance_days'),
            'outstanding' => $request->query('outstanding'),
        ];

        return response()->json($this->memberService->index($tenant->id, $currentUser, $perPage, $search, $isTemp, $planId, $filters));
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
        $this->normalizeNameInput($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'gender' => ['required', 'in:male,female'],
            'email' => [
                'nullable',
                'email',
                Rule::unique('members'),
                Rule::unique('users'),
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
        $this->normalizeNameInput($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'email' => [
                'nullable',
                'email',
                Rule::unique('members'),
            ],
        ]);

        $member = $this->memberService->storeTemp($tenant, $validated);

        return response()->json([
            'message' => 'Temporary member created successfully.',
            'data' => ['id' => $member->id],
        ], 201);
    }

    public function show(Request $request, Member $member): JsonResponse
    {
        $this->memberService->ensureTenantMember($member, app('tenant')->id);
        $canEdit = $request->user()->hasPermission('members.edit') || $request->user()->hasPermission('users.edit');

        return response()->json([
            'data' => $this->memberService->show($member),
            'permissions' => [
                'edit' => $canEdit,
                'verify' => $canEdit || $this->canVerifyCampaignMember($request, $member),
                'delete' => $request->user()->hasPermission('members.delete') || $request->user()->hasPermission('users.delete'),
            ],
        ]);
    }

    public function update(Request $request, Member $member): JsonResponse
    {
        $this->memberService->ensureTenantMember($member, app('tenant')->id);
        $this->normalizeNameInput($request);

        $memberEmailRule = Rule::unique('members')->ignore($member->id);

        $userEmailRule = Rule::unique('users');

        if ($member->user_id) {
            $userEmailRule = $userEmailRule->ignore($member->user_id);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'gender' => ['required', 'in:male,female'],
            'email' => ['nullable', 'email', $memberEmailRule, $userEmailRule],
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
        abort_unless($this->canToggleVerification(request(), $member), 403, 'You do not have permission to verify this member.');

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
        $fromDate = sprintf('%04d-01-01', $year);
        $toDate = sprintf('%04d-12-31', $year);
        $includePictureUrls = $request->boolean('include_picture_urls', false);

        $records = MemberAttendance::query()
            ->where(function ($q) use ($member) {
                $q->where('member_id', $member->id)
                    ->orWhere(function ($q2) use ($member) {
                        if ($member->biometric_member_id) {
                            $q2->whereNull('member_id')
                                ->where('legacy_member_id', $member->biometric_member_id);
                        } else {
                            $q2->whereRaw('0=1');
                        }
                    });
            })
            ->whereBetween('attended_date', [$fromDate, $toDate])
            ->orderBy('attended_date')
            ->with([
                'biometricAccessEvent' => function ($q) {
                    $q->select(['id', 'event_time', 'picture_path']);
                },
            ])
            ->get(['id', 'attended_date', 'biometric_access_event_id']);

        $records = $records->map(function (MemberAttendance $attendance) use ($includePictureUrls) {
            $event = $attendance->biometricAccessEvent;

            return [
                'id' => $attendance->id,
                'attended_date' => optional($attendance->attended_date)->toDateString(),
                'biometric_access_event_id' => $event?->id,
                'biometric_access_event_link' => $event?->id ? '/#/settings/biometric?event_id=' . $event->id : null,
                'biometric_access_event_time' => optional($event?->event_time)?->toIso8601String(),
                'biometric_access_event_has_picture' => (bool) ($event?->picture_path),
                'biometric_access_event_picture_url' => $includePictureUrls && $event?->picture_path
                    ? $this->media->url($event->picture_path)
                    : null,
            ];
        })->values();

        return response()->json([
            'data' => $records,
            'total' => $records->count(),
            'year' => $year,
        ]);
    }

    private function canToggleVerification(Request $request, Member $member): bool
    {
        $user = $request->user();

        if ($user->hasPermission('members.edit') || $user->hasPermission('users.edit')) {
            return true;
        }

        return $this->canVerifyCampaignMember($request, $member);
    }

    private function canVerifyCampaignMember(Request $request, Member $member): bool
    {
        return $member->registration_source === 'campaign'
            && $request->user()->hasPermission('campaigns.verify');
    }

    private function normalizeNameInput(Request $request): void
    {
        if (filled($request->input('name'))) {
            $request->merge([
                'name' => trim((string) $request->input('name')),
            ]);

            return;
        }

        $name = trim(
            trim((string) $request->input('first_name', '')) . ' ' . trim((string) $request->input('last_name', '')),
        );

        if ($name !== '') {
            $request->merge(['name' => $name]);
        }
    }
}
