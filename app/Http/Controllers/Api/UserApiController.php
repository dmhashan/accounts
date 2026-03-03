<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Rules\UniqueTenantEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserApiController extends Controller
{
    public function meta(): JsonResponse
    {
        $memberRole = Role::where('slug', 'member')->first();

        $roles = Role::query()
            ->when($memberRole, fn ($query) => $query->where('id', '!=', $memberRole->id))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'roles' => $roles,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $tenant = app('tenant');
        $currentUser = $request->user();
        $perPage = min((int) $request->integer('per_page', 10), 50);
        $search = trim((string) $request->query('search', ''));

        $memberRole = Role::where('slug', 'member')->first();

        $users = User::query()
            ->where('tenant_id', $tenant->id)
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

        return response()->json([
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
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', new UniqueTenantEmail($tenant->id)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', Rule::exists('roles', 'id')],
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
        ]);

        $user->load('role:id,name,slug');

        return response()->json([
            'message' => 'User created successfully.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        if ($user->tenant_id !== app('tenant')->id) {
            abort(404);
        }

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
            ],
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        if ($user->tenant_id !== app('tenant')->id) {
            abort(404);
        }

        $tenant = app('tenant');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', new UniqueTenantEmail($tenant->id, $user->id)],
            'role_id' => ['required', Rule::exists('roles', 'id')],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

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

        return response()->json([
            'message' => 'User updated successfully.',
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->tenant_id !== app('tenant')->id) {
            abort(404);
        }

        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'You cannot delete yourself.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }
}
