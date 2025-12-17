<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users', 'permissions')->get();
        return view('roles.index', compact('roles'));
    }

    public function show(Role $role)
    {
        $role->load('permissions');
        $allPermissions = Permission::all()->groupBy('feature');
        
        return view('roles.show', compact('role', 'allPermissions'));
    }

    public function updatePermissions(Request $request, Role $role)
    {
        // Prevent editing predefined roles permissions structure
        if (!$role->is_editable) {
            return redirect()->route('roles.show', $role)
                ->with('error', 'This role is predefined and its base permissions cannot be modified.');
        }

        $validated = $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('roles.show', $role)
            ->with('success', 'Permissions updated successfully.');
    }
}

