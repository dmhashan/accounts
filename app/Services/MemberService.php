<?php

namespace App\Services;

use App\Models\Member;
use App\Models\PaymentPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberService
{
    public function __construct(
        private readonly MediaStorageService $media,
        private readonly BiometricSyncService $biometric,
        private readonly AutomatedMemberNotificationService $notifications,
    ) {}

    public function meta(): array
    {
        return [
            'generated_member_id' => Member::generateBiometricMemberId(0), // preview only
        ];
    }

    public function index(int $tenantId, User $currentUser, int $perPage, string $search, ?bool $isTemp = null, ?int $planId = null): array
    {
        $members = Member::query()
            ->when($isTemp !== null, fn ($q) => $q->where('is_temp', $isTemp))
            ->when($planId !== null, fn ($q) => $q->where('payment_plan_id', $planId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('biometric_member_id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => collect($members->items())->map(function (Member $member) {
                [$firstName, $lastName] = $this->resolveFirstAndLastName($member);

                return [
                    'id' => $member->id,
                    'biometric_member_id' => $member->biometric_member_id,
                    'name' => $member->name,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $member->email,
                    'gender' => $member->gender,
                    'phone_number' => $member->phone_number,
                    'allow_sms' => (bool) $member->allow_sms,
                    'allow_whatsapp' => (bool) $member->allow_whatsapp,
                    'whatsapp_number' => $member->whatsapp_number,
                    'is_active' => (bool) $member->is_active,
                    'is_verified' => (bool) $member->is_verified,
                    'is_temp' => (bool) $member->is_temp,
                ];
            }),
            'meta' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
            'permissions' => [
                'create' => $currentUser->hasPermission('members.create') || $currentUser->hasPermission('users.create'),
                'edit' => $currentUser->hasPermission('members.edit') || $currentUser->hasPermission('users.edit'),
                'delete' => $currentUser->hasPermission('members.delete') || $currentUser->hasPermission('users.delete'),
            ],
        ];
    }

    public function exportGoogleContacts(Tenant $tenant): StreamedResponse
    {
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
        $fileName = 'google-contacts-members-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($headers, $tenantName) {
            $output = fopen('php://output', 'w');

            fputcsv($output, $headers);

            foreach (Member::query()->orderBy('created_at', 'desc')->cursor() as $member) {
                [$firstName, $lastName] = $this->resolveFirstAndLastName($member);

                $fileAs = trim($firstName . ' ' . $lastName);

                if ($fileAs === '') {
                    $fileAs = trim((string) ($member->name ?? ''));
                }

                $genderLabel = $member->gender === 'female' ? 'Female' : 'Male';
                $namePrefix = trim($tenantName . ' ' . $genderLabel . ' ' . (string) ($member->biometric_member_id ?? ''));

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
                    (string) ($member->biometric_member_id ?? ''),
                    (string) ($member->comment ?? ''),
                    'Members',
                ]);
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function storeTemp(Tenant $tenant, array $validated): Member
    {
        $firstName = trim($validated['first_name'] ?? '');
        $lastName = trim($validated['last_name'] ?? '');

        $validated['biometric_member_id'] = Member::generateBiometricMemberId($tenant->id);
        $validated['name'] = trim("$firstName $lastName") ?: $firstName ?: $lastName;
        $validated['is_active'] = true;
        $validated['is_verified'] = false;
        $validated['is_temp'] = true;

        return Member::create($validated);
    }

    public function store(Tenant $tenant, array $validated): Member
    {
        $validated['biometric_member_id'] = Member::generateBiometricMemberId($tenant->id);
        $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
        $validated['is_active'] = true;
        $validated['is_verified'] = true;

        if (!empty($validated['payment_plan_id'])) {
            $plan = PaymentPlan::find($validated['payment_plan_id']);

            if ($plan) {
                $validated['payment_plan'] = $plan->name;
                $validated['price'] = $plan->price;
            }
        }

        $member = Member::create($validated);

        $this->biometric->syncMember($member, 'create');
        $this->notifications->sendWelcome($member);

        return $member;
    }

    public function show(Member $member): array
    {
        $this->syncMissingProfilePhotoFromBiometric($member);

        [$firstName, $lastName] = $this->resolveFirstAndLastName($member);

        return [
            'id' => $member->id,
            'biometric_member_id' => $member->biometric_member_id,
            'name' => $member->name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $member->username,
            'gender' => $member->gender,
            'email' => $member->email,
            'phone_number' => $member->phone_number,
            'allow_sms' => (bool) $member->allow_sms,
            'allow_whatsapp' => (bool) $member->allow_whatsapp,
            'whatsapp_number' => $member->whatsapp_number,
            'nic' => $member->nic,
            'date_of_birth' => optional($member->date_of_birth)->format('Y-m-d'),
            'age' => null,
            'address' => $member->address,
            'member_role' => null,
            'admission_fee' => $member->admission_fee,
            'payment_plan_id' => $member->payment_plan_id,
            'payment_plan' => $member->payment_plan,
            'price' => $member->price,
            'current_balance' => $member->current_balance,
            'joined_date' => optional($member->joined_date)->format('Y-m-d'),
            'comment' => $member->comment,
            'is_active' => (bool) $member->is_active,
            'is_verified' => (bool) $member->is_verified,
            'is_temp' => (bool) $member->is_temp,
            'profile_photo_url' => $member->profile_photo_path
                ? $this->media->url($member->profile_photo_path)
                : null,
            'created_at' => optional($member->created_at)->toDateString(),
            'biometric_last_synced_at' => optional($member->biometric_last_synced_at)->toISOString(),
        ];
    }

    private function syncMissingProfilePhotoFromBiometric(Member $member): void
    {
        if ($member->profile_photo_path || !$member->biometric_member_id) {
            return;
        }

        try {
            $deviceInfo = $this->biometric->getMemberDeviceInfo($member);

            if (($deviceInfo['connection_failed'] ?? false) || ($deviceInfo['not_assigned'] ?? false) || ($deviceInfo['not_found'] ?? false)) {
                return;
            }

            if (!(bool) ($deviceInfo['face']['enrolled'] ?? false)) {
                return;
            }

            $this->biometric->uploadFaceAsAvatar($member);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function update(Member $member, array $validated): void
    {
        $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);

        if (!empty($validated['payment_plan_id'])) {
            $plan = PaymentPlan::find($validated['payment_plan_id']);

            if ($plan) {
                $validated['payment_plan'] = $plan->name;
                $validated['price'] = $plan->price;
            }
        }

        $member->update($validated);

        if ($member->user) {
            $member->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'username' => $validated['username'],
            ]);
        }

        $this->biometric->syncMember($member, 'update');
    }

    public function toggleStatus(Member $member): array
    {
        $member->update([
            'is_active' => !$member->is_active,
        ]);

        return [
            'message' => $member->is_active ? 'Member activated successfully.' : 'Member deactivated successfully.',
            'is_active' => (bool) $member->is_active,
        ];
    }

    public function toggleVerification(Member $member): array
    {
        $member->update([
            'is_verified' => !$member->is_verified,
        ]);

        return [
            'message' => $member->is_verified ? 'Member verified successfully.' : 'Member unverified successfully.',
            'is_verified' => (bool) $member->is_verified,
        ];
    }

    public function destroy(Member $member): void
    {
        $this->biometric->syncMember($member, 'delete');

        if ($member->user) {
            $member->user->delete();
        }

        $this->deleteAvatar($member);
        $member->delete();
    }

    public function uploadAvatar(Member $member, UploadedFile $file): string
    {
        if ($member->profile_photo_path) {
            $this->media->delete($member->profile_photo_path);
        }

        $path = $this->media->store($file, 'member-avatars');

        $member->update(['profile_photo_path' => $path]);

        return $this->media->url($path);
    }

    public function deleteAvatar(Member $member): void
    {
        if (!$member->profile_photo_path) {
            return;
        }

        $this->media->delete($member->profile_photo_path);
        $member->update(['profile_photo_path' => null]);
    }

    public function ensureTenantMember(Member $member, int $tenantId): void
    {
        //
    }

    private function resolveFirstAndLastName(Member $member): array
    {
        $firstName = trim((string) ($member->first_name ?? ''));
        $lastName = trim((string) ($member->last_name ?? ''));

        if ($firstName !== '' && $lastName !== '') {
            return [$firstName, $lastName];
        }

        $parts = preg_split('/\s+/', trim((string) ($member->name ?? '')), 2);

        return [
            $firstName !== '' ? $firstName : ($parts[0] ?? ''),
            $lastName !== '' ? $lastName : ($parts[1] ?? ''),
        ];
    }
}
