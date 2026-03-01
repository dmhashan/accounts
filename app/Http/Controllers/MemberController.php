<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
        $generatedMemberId = Member::generateMemberId();

        return view('members.create', compact('generatedMemberId'));
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'username' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('members')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
                Rule::unique('users')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'gender' => 'required|in:male,female',
            'email' => [
                'required',
                'email',
                Rule::unique('members')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
                Rule::unique('users')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'phone_number' => 'required|string|max:20',
            'nic' => 'nullable|string|max:50',
            'date_of_birth' => 'required|date|before_or_equal:today',
            'age' => 'required|integer|min:1|max:120',
            'address' => 'nullable|string|max:1000',
            'member_role' => 'required|string|max:50',
            'admission_fee' => 'nullable|numeric|min:0',
            'payment_plan' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'joined_date' => 'required|date',
            'comment' => 'nullable|string|max:2000',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Generate member ID server-side and compose full name
        $validated['member_id'] = Member::generateMemberId();
        $validated['tenant_id'] = $tenant->id;
        $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
        $validated['is_active'] = true;
        $validated['is_verified'] = true; // Admin-created members are verified

        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo_path'] = $request->file('profile_photo')->store('member-photos', 'public');
        }

        // Create member
        $member = Member::create($validated);

        // Create user account with member role
        $memberRole = Role::where('slug', 'member')->first();

        if ($memberRole) {
            $user = User::create([
                'tenant_id' => $tenant->id,
                'role_id' => $memberRole->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'username' => $validated['username'],
                'password' => Hash::make(Str::random(40)),
            ]);

            // Link member to user
            $member->update(['user_id' => $user->id]);
        }

        return redirect()->route('members.index')
            ->with('success', 'Member created successfully.');
    }

    public function edit(Member $member)
    {
        // Ensure member belongs to current tenant
        if ($member->tenant_id !== app('tenant')->id) {
            abort(403);
        }

        if (!$member->first_name || !$member->last_name) {
            $parts = preg_split('/\s+/', trim($member->name ?? ''), 2);
            $member->first_name = $member->first_name ?: ($parts[0] ?? '');
            $member->last_name = $member->last_name ?: ($parts[1] ?? '');
        }

        return view('members.edit', compact('member'));
    }

    public function update(Request $request, Member $member)
    {
        // Ensure member belongs to current tenant
        if ($member->tenant_id !== app('tenant')->id) {
            abort(403);
        }

        $tenant = app('tenant');

        $memberUsernameRule = Rule::unique('members')
            ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
            ->ignore($member->id);

        $memberEmailRule = Rule::unique('members')
            ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
            ->ignore($member->id);

        $userUsernameRule = Rule::unique('users')
            ->where(fn ($q) => $q->where('tenant_id', $tenant->id));

        $userEmailRule = Rule::unique('users')
            ->where(fn ($q) => $q->where('tenant_id', $tenant->id));

        if ($member->user_id) {
            $userUsernameRule = $userUsernameRule->ignore($member->user_id);
            $userEmailRule = $userEmailRule->ignore($member->user_id);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'username' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                $memberUsernameRule,
                $userUsernameRule,
            ],
            'gender' => 'required|in:male,female',
            'email' => [
                'required',
                'email',
                $memberEmailRule,
                $userEmailRule,
            ],
            'phone_number' => 'required|string|max:20',
            'nic' => 'nullable|string|max:50',
            'date_of_birth' => 'required|date|before_or_equal:today',
            'age' => 'required|integer|min:1|max:120',
            'address' => 'nullable|string|max:1000',
            'member_role' => 'required|string|max:50',
            'admission_fee' => 'nullable|numeric|min:0',
            'payment_plan' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'joined_date' => 'required|date',
            'comment' => 'nullable|string|max:2000',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);

        if ($request->hasFile('profile_photo')) {
            if ($member->profile_photo_path) {
                Storage::disk('public')->delete($member->profile_photo_path);
            }

            $validated['profile_photo_path'] = $request->file('profile_photo')->store('member-photos', 'public');
        }

        $member->update($validated);

        // Update linked user email and name
        if ($member->user) {
            $member->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'username' => $validated['username'],
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

    public function toggleVerification(Member $member)
    {
        // Ensure member belongs to current tenant
        if ($member->tenant_id !== app('tenant')->id) {
            abort(403);
        }

        $member->update([
            'is_verified' => !$member->is_verified
        ]);

        $status = $member->is_verified ? 'verified' : 'unverified';
        
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

        if ($member->profile_photo_path) {
            Storage::disk('public')->delete($member->profile_photo_path);
        }

        $member->delete();

        return redirect()->route('members.index')
            ->with('success', 'Member deleted successfully.');
    }

    public function profile()
    {
        // For members, show only their own profile
        $member = Member::where('tenant_id', app('tenant')->id)
            ->where('user_id', auth()->id())
            ->with('user')
            ->firstOrFail();
        
        return view('members.profile', compact('member'));
    }
}
