<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function meta(): array
    {
        $memberRole = Role::where('slug', 'member')->first();

        $roles = Role::query()
            ->when($memberRole, fn ($query) => $query->where('id', '!=', $memberRole->id))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return [
            'roles' => $roles,
        ];
    }

    public function index(int $tenantId, User $currentUser, int $perPage, string $search): array
    {
        $memberRole = Role::where('slug', 'member')->first();

        $users = User::query()
            ->where('tenant_id', $tenantId)
            ->when($memberRole, fn ($query) => $query->where('role_id', '!=', $memberRole->id))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->with('role:id,name,slug')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => collect($users->items())->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ? [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                    'slug' => $user->role->slug,
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
            'tenant_id' => $tenantId,
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
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
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
        if ($user->tenant_id !== $tenantId) {
            abort(404);
        }
    }
}
