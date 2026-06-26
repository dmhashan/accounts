<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleApiController extends Controller
{
    public function __construct(private readonly RoleService $roleService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) $request->integer('per_page', 12), 50);

        return response()->json($this->roleService->index($user, $perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('roles', 'slug')],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $role = $this->roleService->store($validated);

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
        return response()->json($this->roleService->show($role));
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        if (!$this->roleService->canEdit($role)) {
            return response()->json([
                'message' => 'This role is predefined and cannot be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('roles', 'slug')->ignore($role->id)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->roleService->update($role, $validated);

        return response()->json([
            'message' => 'Role updated successfully.',
        ]);
    }

    public function updatePermissions(Request $request, Role $role): JsonResponse
    {
        if (!$this->roleService->canEdit($role)) {
            return response()->json([
                'message' => 'This role is predefined and its permissions cannot be modified.',
            ], 422);
        }

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $this->roleService->updatePermissions($role, $validated['permissions'] ?? []);

        return response()->json([
            'message' => 'Permissions updated successfully.',
        ]);
    }
}
