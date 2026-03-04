<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberApiController extends Controller
{
    public function meta(): JsonResponse
    {
        return response()->json([
            'generated_member_id' => Member::generateMemberId(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $perPage = min((int) $request->integer('per_page', 15), 50);
        $search = trim((string) $request->query('search', ''));

        $members = Member::query()
            ->where('tenant_id', app('tenant')->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('member_id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => collect($members->items())->map(function (Member $member) {
                $firstName = $member->first_name;
                $lastName = $member->last_name;

                if (!$firstName || !$lastName) {
                    $parts = preg_split('/\s+/', trim($member->name ?? ''), 2);
                    $firstName = $firstName ?: ($parts[0] ?? '');
                    $lastName = $lastName ?: ($parts[1] ?? '');
                }

                return [
                    'id' => $member->id,
                    'member_id' => $member->member_id,
                    'name' => $member->name,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $member->email,
                    'gender' => $member->gender,
                    'phone_number' => $member->phone_number,
                    'is_active' => (bool) $member->is_active,
                    'is_verified' => (bool) $member->is_verified,
                ];
            }),
            'meta' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
            'permissions' => [
                'create' => $currentUser->hasPermission('users.create'),
                'edit' => $currentUser->hasPermission('users.edit'),
                'delete' => $currentUser->hasPermission('users.delete'),
            ],
        ]);
    }

    public function exportGoogleContacts(): StreamedResponse
    {
        $tenant = app('tenant');

        $headers = [
            'Name Prefix',
            'First Name',
            'Middle Name',
            'Last Name',
            'Name Suffix',
            'Phonetic First Name',
            'Phonetic Middle Name',
            'Phonetic Last Name',
            'Nickname',
            'File As',
            'E-mail 1 - Label',
            'E-mail 1 - Value',
            'Phone 1 - Label',
            'Phone 1 - Value',
            'Address 1 - Label',
            'Address 1 - Country',
            'Address 1 - Street',
            'Address 1 - Extended Address',
            'Address 1 - City',
            'Address 1 - Region',
            'Address 1 - Postal Code',
            'Address 1 - PO Box',
            'Organization Name',
            'Organization Title',
            'Organization Department',
            'Birthday',
            'Event 1 - Label',
            'Event 1 - Value',
            'Relation 1 - Label',
            'Relation 1 - Value',
            'Website 1 - Label',
            'Website 1 - Value',
            'Custom Field 1 - Label',
            'Custom Field 1 - Value',
            'Notes',
            'Labels',
        ];

        $tenantId = $tenant->id;
        $tenantName = (string) $tenant->name;
        $fileName = 'google-contacts-members-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($headers, $tenantId, $tenantName) {
            $output = fopen('php://output', 'w');

            fputcsv($output, $headers);

            foreach (Member::query()->where('tenant_id', $tenantId)->orderBy('created_at', 'desc')->cursor() as $member) {
                $firstName = trim((string) ($member->first_name ?? ''));
                $lastName = trim((string) ($member->last_name ?? ''));

                if (!$firstName || !$lastName) {
                    $parts = preg_split('/\s+/', trim((string) ($member->name ?? '')), 2);
                    $firstName = $firstName ?: ($parts[0] ?? '');
                    $lastName = $lastName ?: ($parts[1] ?? '');
                }

                $fileAs = trim($firstName.' '.$lastName);
                if ($fileAs === '') {
                    $fileAs = trim((string) ($member->name ?? ''));
                }

                $genderLabel = $member->gender === 'female' ? 'Female' : 'Male';
                $namePrefix = trim($tenantName.' '.$genderLabel.' '.(string) ($member->member_id ?? ''));

                fputcsv($output, [
                    '',
                    $namePrefix,
                    $firstName,
                    $lastName,
                    '',
                    '',
                    '',
                    '',
                    '',
                    $fileAs,
                    '* Home',
                    (string) ($member->email ?? ''),
                    '* Mobile',
                    (string) ($member->phone_number ?? ''),
                    '* Home',
                    '',
                    (string) ($member->address ?? ''),
                    '',
                    '',
                    '',
                    '',
                    '',
                    $tenantName,
                    (string) ($member->member_role ?? ''),
                    '',
                    optional($member->date_of_birth)->format('Y-m-d'),
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Member ID',
                    (string) ($member->member_id ?? ''),
                    (string) ($member->comment ?? ''),
                    'Members',
                ]);
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
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

        $validated['member_id'] = Member::generateMemberId();
        $validated['tenant_id'] = $tenant->id;
        $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
        $validated['is_active'] = true;
        $validated['is_verified'] = true;

        $member = Member::create($validated);

        $memberRole = Role::where('slug', 'member')->first();
        if ($memberRole) {
            $user = User::create([
                'tenant_id' => $tenant->id,
                'role_id' => $memberRole->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'username' => $validated['username'],
                'password' => Hash::make(Str::random(40)),
            ]);

            $member->update(['user_id' => $user->id]);
        }

        return response()->json([
            'message' => 'Member created successfully.',
            'data' => ['id' => $member->id],
        ], 201);
    }

    public function show(Member $member): JsonResponse
    {
        if ($member->tenant_id !== app('tenant')->id) {
            abort(404);
        }

        if (!$member->first_name || !$member->last_name) {
            $parts = preg_split('/\s+/', trim($member->name ?? ''), 2);
            $member->first_name = $member->first_name ?: ($parts[0] ?? '');
            $member->last_name = $member->last_name ?: ($parts[1] ?? '');
        }

        return response()->json([
            'data' => [
                'id' => $member->id,
                'member_id' => $member->member_id,
                'first_name' => $member->first_name,
                'last_name' => $member->last_name,
                'username' => $member->username,
                'gender' => $member->gender,
                'email' => $member->email,
                'phone_number' => $member->phone_number,
                'nic' => $member->nic,
                'date_of_birth' => optional($member->date_of_birth)->format('Y-m-d'),
                'age' => $member->age,
                'address' => $member->address,
                'member_role' => $member->member_role,
                'admission_fee' => $member->admission_fee,
                'payment_plan' => $member->payment_plan,
                'price' => $member->price,
                'joined_date' => optional($member->joined_date)->format('Y-m-d'),
                'comment' => $member->comment,
            ],
        ]);
    }

    public function update(Request $request, Member $member): JsonResponse
    {
        if ($member->tenant_id !== app('tenant')->id) {
            abort(404);
        }

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

        $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
        $member->update($validated);

        if ($member->user) {
            $member->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'username' => $validated['username'],
            ]);
        }

        return response()->json([
            'message' => 'Member updated successfully.',
        ]);
    }

    public function toggleStatus(Member $member): JsonResponse
    {
        if ($member->tenant_id !== app('tenant')->id) {
            abort(404);
        }

        $member->update([
            'is_active' => !$member->is_active,
        ]);

        return response()->json([
            'message' => $member->is_active ? 'Member activated successfully.' : 'Member deactivated successfully.',
            'is_active' => (bool) $member->is_active,
        ]);
    }

    public function toggleVerification(Member $member): JsonResponse
    {
        if ($member->tenant_id !== app('tenant')->id) {
            abort(404);
        }

        $member->update([
            'is_verified' => !$member->is_verified,
        ]);

        return response()->json([
            'message' => $member->is_verified ? 'Member verified successfully.' : 'Member unverified successfully.',
            'is_verified' => (bool) $member->is_verified,
        ]);
    }

    public function destroy(Member $member): JsonResponse
    {
        if ($member->tenant_id !== app('tenant')->id) {
            abort(404);
        }

        if ($member->user) {
            $member->user->delete();
        }

        $member->delete();

        return response()->json([
            'message' => 'Member deleted successfully.',
        ]);
    }
}
