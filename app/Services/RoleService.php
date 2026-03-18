<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class RoleService
{
    public function index(?User $user, int $perPage): array
    {
        $roles = Role::query()
            ->withCount('users', 'permissions')
            ->orderBy('name')
            ->paginate($perPage);

        return [
            'data' => collect($roles->items())->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_editable' => (bool) $role->is_editable,
                'users_count' => $role->users_count,
                'permissions_count' => $role->permissions_count,
            ]),
            'meta' => [
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
            ],
            'permissions' => [
                'view' => $user?->hasPermission('roles.view') ?? false,
                'managePermissions' => $user?->hasPermission('roles.permissions') ?? false,
            ],
        ];
    }

    public function store(array $validated): Role
    {
        return Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'is_editable' => true,
        ]);
    }

    public function show(Role $role): array
    {
        $role->load('permissions:id,name,slug,feature,description');
        $allPermissions = Permission::query()
            ->orderBy('feature')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'feature', 'description'])
            ->groupBy('feature');

        return [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_editable' => (bool) $role->is_editable,
                'permission_ids' => $role->permissions->pluck('id')->values(),
            ],
            'permissions' => $allPermissions,
        ];
    }

    public function update(Role $role, array $validated): void
    {
        $role->update($validated);
    }

    public function updatePermissions(Role $role, array $permissionIds): void
    {
        $role->permissions()->sync($permissionIds);
    }

    public function canEdit(Role $role): bool
    {
        return (bool) $role->is_editable;
    }
}
