<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Rules\UniqueTenantEmail;
use App\Services\PasswordResetService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserApiController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly PasswordResetService $passwordResetService,
    ) {}

    public function meta(): JsonResponse
    {
        return response()->json($this->userService->meta());
    }

    public function index(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        /** @var User $currentUser */
        $currentUser = $request->user();

        $perPage = min((int) $request->integer('per_page', 10), 50);
        $search = trim((string) $request->query('search', ''));

        return response()->json($this->userService->index($tenant->id, $currentUser, $perPage, $search));
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', new UniqueTenantEmail($tenant->id)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', Rule::exists('roles', 'id')],
        ]);

        $user = $this->userService->store($tenant->id, $validated);

        return response()->json([
            'message' => 'User created successfully.',
            'data' => $this->userService->show($user),
        ], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->userService->ensureTenantUser($user, app('tenant')->id);

        return response()->json([
            'data' => $this->userService->show($user, $request->user()),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->userService->ensureTenantUser($user, app('tenant')->id);

        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', new UniqueTenantEmail($tenant->id, $user->id)],
            'role_id' => ['required', Rule::exists('roles', 'id')],
        ]);

        $this->userService->update($user, $validated);

        return response()->json([
            'message' => 'User updated successfully.',
        ]);
    }

    public function sendPasswordReset(User $user): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $this->userService->ensureTenantUser($user, $tenant->id);
        $this->passwordResetService->sendResetLink($user, $tenant);

        return response()->json([
            'message' => 'Password reset link has been sent to ' . $user->email . '.',
        ]);
    }

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $this->userService->ensureTenantUser($user, app('tenant')->id);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $isActive = (bool) $validated['is_active'];

        if (!$this->userService->setActive($user, $request->user(), $isActive)) {
            return response()->json([
                'message' => 'You cannot deactivate yourself.',
            ], 422);
        }

        return response()->json([
            'message' => $isActive ? 'User activated successfully.' : 'User deactivated successfully.',
            'data' => $this->userService->show($user->fresh(), $request->user()),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->userService->ensureTenantUser($user, app('tenant')->id);

        if (!$this->userService->destroy($user, $request->user())) {
            return response()->json([
                'message' => 'You cannot delete yourself.',
            ], 422);
        }

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }
}
