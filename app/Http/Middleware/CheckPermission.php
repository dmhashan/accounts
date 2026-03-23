<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = $request->user();

        $allowed = collect($permissions)
            ->flatMap(fn (string $permission) => array_filter(array_map('trim', explode(',', $permission))))
            ->contains(fn (string $permission) => $user?->hasPermission($permission) ?? false);

        if (!$allowed) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}

