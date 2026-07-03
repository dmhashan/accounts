<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index()
    {
        $members = Member::query()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('members.index', compact('members'));
    }

    public function create()
    {
        $generatedMemberId = Member::generateBiometricMemberId(app('tenant')->id);

        return view('members.create', compact('generatedMemberId'));
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'gender' => 'required|in:male,female',
            'email' => [
                'nullable',
                'email',
                Rule::unique('members'),
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

        $validated['email'] = filled($validated['email'] ?? null)
            ? trim((string) $validated['email'])
            : null;

        // Generate member ID server-side and compose full name
        $validated['biometric_member_id'] = Member::generateBiometricMemberId($tenant->id);
        $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
        $validated['is_active'] = true;
        $validated['is_verified'] = true; // Admin-created members are verified

        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo_path'] = $request->file('profile_photo')->store('member-photos', 'public');
        }

        // Create member
        $member = Member::create($validated);

        return redirect()->route('members.index')
            ->with('success', 'Member created successfully.');
    }

    public function edit(Member $member)
    {
        // Ensure member belongs to current tenant

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

        $memberEmailRule = Rule::unique('members')->ignore($member->id);

        $userEmailRule = Rule::unique('users');

        if ($member->user_id) {
            $userEmailRule = $userEmailRule->ignore($member->user_id);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'gender' => 'required|in:male,female',
            'email' => [
                'nullable',
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

        $validated['email'] = filled($validated['email'] ?? null)
            ? trim((string) $validated['email'])
            : null;
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
            $userData = [
                'name' => $validated['name'],
            ];

            if (!empty($validated['email'])) {
                $userData['email'] = $validated['email'];
            }

            $member->user->update($userData);
        }

        return redirect()->route('members.index')
            ->with('success', 'Member updated successfully.');
    }

    public function toggleStatus(Member $member)
    {
        // Ensure member belongs to current tenant

        $member->update([
            'is_active' => !$member->is_active,
        ]);

        $status = $member->is_active ? 'activated' : 'deactivated';

        return redirect()->route('members.index')
            ->with('success', "Member {$status} successfully.");
    }

    public function toggleVerification(Member $member)
    {
        // Ensure member belongs to current tenant

        $member->update([
            'is_verified' => !$member->is_verified,
        ]);

        $status = $member->is_verified ? 'verified' : 'unverified';

        return redirect()->route('members.index')
            ->with('success', "Member {$status} successfully.");
    }

    public function destroy(Member $member)
    {
        // Ensure member belongs to current tenant

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
        $member = Member::query()
            ->where('user_id', auth()->id())
            ->with('user')
            ->firstOrFail();

        return view('members.profile', compact('member'));
    }
}
