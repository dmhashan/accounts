<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthSessionService
{
    public function attemptTenantLogin(int $tenantId, string $login, string $password): bool
    {
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;

        return Auth::attempt([
            'tenant_id' => $tenantId,
            'password' => $password,
            $isEmail ? 'email' : 'username' => $login,
        ]);
    }

    public function regenerateSession(Request $request): void
    {
        $request->session()->regenerate();
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function refreshSession(Request $request): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return true;
    }
}
