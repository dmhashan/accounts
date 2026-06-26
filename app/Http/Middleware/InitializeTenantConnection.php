<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Tenancy\TenantDatabaseManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenantConnection
{
    public function __construct(private readonly TenantDatabaseManager $tenancy) {}

    /**
     * Activate the tenant database before StartSession hydrates the authenticated user.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, \Closure $next): Response
    {
        $this->tenancy->deactivate();
        $domain = $this->tenancy->domainForRequest($request);
        $tenant = $domain ? $this->tenancy->activateByDomain($domain) : null;

        if ($tenant instanceof Tenant) {
            $request->merge(['tenant' => $tenant]);

            return $next($request);
        }

        if ($this->routeRequiresTenant($request)) {
            return redirect('/');
        }

        return $next($request);
    }

    private function routeRequiresTenant(Request $request): bool
    {
        $route = $request->route();

        return $route !== null
            && in_array(IdentifyTenant::class, $route->gatherMiddleware(), true);
    }
}
