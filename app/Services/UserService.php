<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function meta(): array
    {
        $roles = Role::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return [
            'roles' => $roles,
        ];
    }

    public function index(int $tenantId, User $currentUser, int $perPage, string $search): array
    {
        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->with([
                'role:id,name,slug',
                'member:id,user_id,biometric_member_id,name,email,phone_number,joined_date,is_active,is_verified',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => collect($users->items())->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role instanceof Role ? [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                    'slug' => $user->role->slug,
                ] : null,
                'member' => $user->member ? [
                    'id' => $user->member->id,
                    'member_id' => $user->member->biometric_member_id,
                    'name' => $user->member->name,
                    'email' => $user->member->email,
                    'phone_number' => $user->member->phone_number,
                    'joined_date' => $user->member->joined_date?->toDateString(),
                    'is_active' => $user->member->is_active,
                    'is_verified' => $user->member->is_verified,
                ] : null,
                'canDelete' => $currentUser->id !== $user->id,
            ]),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
            'permissions' => [
                'create' => $currentUser->hasPermission('users.create'),
                'edit' => $currentUser->hasPermission('users.edit'),
                'delete' => $currentUser->hasPermission('users.delete'),
            ],
        ];
    }

    public function store(int $tenantId, array $validated): User
    {
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
        ]);

        $user->load('role:id,name,slug');

        return $user;
    }

    public function show(User $user): array
    {
        $user->loadMissing([
            'role:id,name,slug',
            'member:id,user_id,biometric_member_id,name,email,phone_number,joined_date,is_active,is_verified',
        ]);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'role' => $user->role instanceof Role ? [
                'id' => $user->role->id,
                'name' => $user->role->name,
                'slug' => $user->role->slug,
            ] : null,
            'member' => $user->member ? [
                'id' => $user->member->id,
                'member_id' => $user->member->biometric_member_id,
                'name' => $user->member->name,
                'email' => $user->member->email,
                'phone_number' => $user->member->phone_number,
                'joined_date' => $user->member->joined_date?->toDateString(),
                'is_active' => $user->member->is_active,
                'is_verified' => $user->member->is_verified,
            ] : null,
        ];
    }

    public function update(User $user, array $validated): void
    {
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
        ]);

        if (!empty($validated['password'])) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }
    }

    public function destroy(User $targetUser, User $actor): bool
    {
        if ($actor->id === $targetUser->id) {
            return false;
        }

        $targetUser->delete();

        return true;
    }

    public function ensureTenantUser(User $user, int $tenantId): void
    {
        //
    }
}
