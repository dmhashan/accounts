<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PortalUserController extends Controller
{
    /**
     * List all portal administrators.
     */
    public function index(Request $request)
    {
        $query = PortalUser::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($users);
    }

    /**
     * Create a new portal administrator.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:central.portal_users,email',
            'mobile_number' => 'required|string|max:30|unique:central.portal_users,mobile_number',
            'is_active' => 'boolean',
        ]);

        $user = PortalUser::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'mobile_number' => $validated['mobile_number'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Portal administrator created successfully.',
            'user' => $user,
        ], 201);
    }

    /**
     * Update portal administrator details.
     */
    public function update(Request $request, $id)
    {
        $user = PortalUser::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('central.portal_users', 'email')->ignore($user->id),
            ],
            'mobile_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('central.portal_users', 'mobile_number')->ignore($user->id),
            ],
            'is_active' => 'boolean',
        ]);

        // Prevent self-deactivation
        if (auth('portal')->id() === $user->id && isset($validated['is_active']) && !$validated['is_active']) {
            return response()->json([
                'message' => 'You cannot deactivate your own portal administrator account.',
            ], 422);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Portal administrator updated successfully.',
            'user' => $user,
        ]);
    }

    /**
     * Delete a portal administrator.
     */
    public function destroy($id)
    {
        $user = PortalUser::findOrFail($id);

        if (auth('portal')->id() === $user->id) {
            return response()->json([
                'message' => 'You cannot delete your own portal administrator account.',
            ], 400);
        }

        $user->delete();

        return response()->json([
            'message' => 'Portal administrator deleted successfully.',
        ]);
    }
}
