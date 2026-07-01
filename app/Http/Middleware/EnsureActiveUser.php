<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    /**
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, \Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->is_active !== false) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your account is deactivated. Please contact an administrator.',
            ], 403);
        }

        return redirect()->route('login.form')
            ->with('error', 'Your account is deactivated. Please contact an administrator.');
    }
}
