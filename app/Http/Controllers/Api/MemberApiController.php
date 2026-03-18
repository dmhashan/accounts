<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Tenant;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberApiController extends Controller
{
    public function __construct(private readonly MemberService $memberService)
    {
    }

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

        return response()->json($this->memberService->index($tenant->id, $currentUser, $perPage, $search));
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
            'nic' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'address' => ['nullable', 'string', 'max:1000'],
            'member_role' => ['required', 'string', 'max:50'],
            'admission_fee' => ['nullable', 'numeric', 'min:0'],
            'payment_plan' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'joined_date' => ['required', 'date'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $member = $this->memberService->store($tenant, $validated);

        return response()->json([
            'message' => 'Member created successfully.',
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
            'nic' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'address' => ['nullable', 'string', 'max:1000'],
            'member_role' => ['required', 'string', 'max:50'],
            'admission_fee' => ['nullable', 'numeric', 'min:0'],
            'payment_plan' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
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
}
