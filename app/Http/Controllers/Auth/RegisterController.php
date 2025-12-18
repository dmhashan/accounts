<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\UniqueTenantEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $tenant = app('tenant');
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female,other'],
            'email' => ['required', 'string', 'email', 'max:255', new UniqueTenantEmail($tenant->id)],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'nic' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Get default member role
        $memberRole = \App\Models\Role::where('slug', 'member')->first();

        // Create user account
        $user = User::create([
            'tenant_id' => $tenant->id,
            'role_id' => $memberRole?->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Create member record with is_verified = false (public registration)
        $member = \App\Models\Member::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'member_id' => \App\Models\Member::generateMemberId(),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'gender' => $validated['gender'],
            'phone_number' => $validated['phone_number'] ?? null,
            'nic' => $validated['nic'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'is_active' => true,
            'is_verified' => false, // Public registration - not verified
        ]);

        auth()->login($user);

        return redirect()->route('dashboard')->with('success', 'Registration successful! Your account is pending verification.');
    }
}
