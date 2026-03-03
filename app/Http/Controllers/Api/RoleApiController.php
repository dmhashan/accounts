<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) $request->integer('per_page', 12), 50);

        $roles = Role::query()
            ->withCount('users', 'permissions')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
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
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('roles', 'slug')],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'is_editable' => true,
        ]);

        return response()->json([
            'message' => 'Role created successfully.',
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
            ],
        ], 201);
    }

    public function show(Role $role): JsonResponse
    {
        $role->load('permissions:id,name,slug,feature,description');
        $allPermissions = Permission::query()
            ->orderBy('feature')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'feature', 'description'])
            ->groupBy('feature');

        return response()->json([
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_editable' => (bool) $role->is_editable,
                'permission_ids' => $role->permissions->pluck('id')->values(),
            ],
            'permissions' => $allPermissions,
        ]);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        if (!$role->is_editable) {
            return response()->json([
                'message' => 'This role is predefined and cannot be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('roles', 'slug')->ignore($role->id)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $role->update($validated);

        return response()->json([
            'message' => 'Role updated successfully.',
        ]);
    }

    public function updatePermissions(Request $request, Role $role): JsonResponse
    {
        if (!$role->is_editable) {
            return response()->json([
                'message' => 'This role is predefined and its permissions cannot be modified.',
            ], 422);
        }

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return response()->json([
            'message' => 'Permissions updated successfully.',
        ]);
    }
}
