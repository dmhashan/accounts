<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Tenancy\TenantDatabaseManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function __construct(private readonly TenantDatabaseManager $tenancy) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, \Closure $next): Response
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        if (!$tenant instanceof Tenant) {
            $domain = $this->tenancy->domainForRequest($request);
            $tenant = $domain ? $this->tenancy->activateByDomain($domain) : null;
        }

        if (!$tenant) {
            return redirect('/');
        }

        if (!$tenant->is_active) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'This tenant has been temporarily blocked.',
                    'blocked' => true,
                ], 403);
            }

            $path = $request->getPathInfo();
            $appType = 'Website';

            if (str_starts_with($path, '/profile')) {
                $appType = 'Member Portal';
            } elseif (str_starts_with($path, '/dashboard') || str_starts_with($path, '/login')) {
                $appType = 'Administrator Portal';
            } elseif (auth()->check()) {
                $appType = 'Administrator Portal';
            }

            return response()->view('tenant-blocked', [
                'tenant' => $tenant,
                'appType' => $appType,
            ]);
        }

        $request->merge(['tenant' => $tenant]);

        return $next($request);
    }
}
