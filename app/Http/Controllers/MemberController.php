<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    public function index()
    {
        $members = Member::where('tenant_id', app('tenant')->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('members.index', compact('members'));
    }

    public function create()
    {
        return view('members.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'email' => 'required|email|unique:members,email',
            'phone_number' => 'nullable|string|max:20',
            'nic' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'comment' => 'nullable|string',
        ]);

        // Generate member ID
        $validated['member_id'] = Member::generateMemberId();
        $validated['tenant_id'] = app('tenant')->id;
        $validated['is_active'] = true;

        // Create member
        $member = Member::create($validated);

        // Create user account with member role
        $memberRole = Role::where('slug', 'member')->first();
        
        if ($memberRole) {
            $user = User::create([
                'tenant_id' => app('tenant')->id,
                'role_id' => $memberRole->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make(Str::random(16)), // Random password, to be reset
            ]);

            // Link member to user
            $member->update(['user_id' => $user->id]);
        }

        return redirect()->route('members.index')
            ->with('success', 'Member created successfully. Login credentials will be sent to the member.');
    }

    public function edit(Member $member)
    {
        // Ensure member belongs to current tenant
        if ($member->tenant_id !== app('tenant')->id) {
            abort(403);
        }

        return view('members.edit', compact('member'));
    }

    public function update(Request $request, Member $member)
    {
        // Ensure member belongs to current tenant
        if ($member->tenant_id !== app('tenant')->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'email' => 'required|email|unique:members,email,' . $member->id,
            'phone_number' => 'nullable|string|max:20',
            'nic' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'comment' => 'nullable|string',
        ]);

        $member->update($validated);

        // Update linked user email and name
        if ($member->user) {
            $member->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);
        }

        return redirect()->route('members.index')
            ->with('success', 'Member updated successfully.');
    }

    public function toggleStatus(Member $member)
    {
        // Ensure member belongs to current tenant
        if ($member->tenant_id !== app('tenant')->id) {
            abort(403);
        }

        $member->update([
            'is_active' => !$member->is_active
        ]);

        $status = $member->is_active ? 'activated' : 'deactivated';
        
        return redirect()->route('members.index')
            ->with('success', "Member {$status} successfully.");
    }

    public function destroy(Member $member)
    {
        // Ensure member belongs to current tenant
        if ($member->tenant_id !== app('tenant')->id) {
            abort(403);
        }

        // Delete linked user if exists
        if ($member->user) {
            $member->user->delete();
        }

        $member->delete();

        return redirect()->route('members.index')
            ->with('success', 'Member deleted successfully.');
    }
}
