<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Rules\UniqueTenantEmail;

class UserController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');
        
        // Get member role ID to exclude
        $memberRole = Role::where('slug', 'member')->first();
        
        $users = User::where('tenant_id', $tenant->id)
            ->when($memberRole, function ($query) use ($memberRole) {
                return $query->where('role_id', '!=', $memberRole->id);
            })
            ->with('role')
            ->paginate(10);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        // Exclude member role from selection
        $memberRole = Role::where('slug', 'member')->first();
        $roles = Role::when($memberRole, function ($query) use ($memberRole) {
            return $query->where('id', '!=', $memberRole->id);
        })->get();
        
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', new UniqueTenantEmail($tenant->id)],
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        // Ensure user belongs to current tenant
        if ($user->tenant_id !== app('tenant')->id) {
            abort(404);
        }

        // Exclude member role from selection
        $memberRole = Role::where('slug', 'member')->first();
        $roles = Role::when($memberRole, function ($query) use ($memberRole) {
            return $query->where('id', '!=', $memberRole->id);
        })->get();
        
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        // Ensure user belongs to current tenant
        if ($user->tenant_id !== app('tenant')->id) {
            abort(404);
        }

        $tenant = app('tenant');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', new UniqueTenantEmail($tenant->id, $user->id)],
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);
            
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        // Ensure user belongs to current tenant
        if ($user->tenant_id !== app('tenant')->id) {
            abort(404);
        }

        // Prevent deleting self
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete yourself.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}

