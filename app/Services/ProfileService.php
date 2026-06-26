<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;

class ProfileService
{
    public function __construct(private readonly MediaStorageService $media) {}

    public function build(User $user, Tenant $tenant): array
    {
        $canViewProfile = $user->hasPermission('member.profile.view');

        if (!$canViewProfile) {
            abort(403);
        }

        $member = Member::query()
            ->where('user_id', $user->id)
            ->first();

        if ($member) {
            $this->hydrateMemberNameParts($member);
        }

        return [
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role?->name,
                'role_slug' => $user->role?->slug,
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
            ],
            'member' => $member ? [
                'id' => $member->id,
                'biometric_member_id' => $member->biometric_member_id,
                'name' => $member->name,
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
                'current_balance' => $member->current_balance,
                'joined_date' => optional($member->joined_date)->format('Y-m-d'),
                'comment' => $member->comment,
                'is_active' => (bool) $member->is_active,
                'is_verified' => (bool) $member->is_verified,
                'profile_photo_url' => $member->profile_photo_path
                    ? $this->media->url($member->profile_photo_path)
                    : null,
                'created_at' => optional($member->created_at)->format('Y-m-d'),
            ] : null,
        ];
    }

    private function hydrateMemberNameParts(Member $member): void
    {
        if ($member->first_name && $member->last_name) {
            return;
        }

        $parts = preg_split('/\s+/', trim($member->name ?? ''), 2);
        $member->first_name = $member->first_name ?: ($parts[0] ?? '');
        $member->last_name = $member->last_name ?: ($parts[1] ?? '');
    }
}
