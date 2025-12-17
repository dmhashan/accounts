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
            'email' => ['required', 'string', 'email', 'max:255', new UniqueTenantEmail($tenant->id)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Get default member role
        $memberRole = \App\Models\Role::where('slug', 'member')->first();

        $user = User::create([
            'tenant_id' => $tenant->id,
            'role_id' => $memberRole?->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        auth()->login($user);

        return redirect()->route('dashboard')->with('success', 'Registration successful!');
    }
}
