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

        $request->merge(['tenant' => $tenant]);

        return $next($request);
    }
}
