<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if multitenancy is bypassed
        if (!config('app.multitenancy_enabled', true)) {
            $bypassDomain = config('app.multitenancy_bypass_domain');
            
            if ($bypassDomain) {
                $tenant = Tenant::where('domain', $bypassDomain)->first();
                
                if ($tenant) {
                    $request->merge(['tenant' => $tenant]);
                    app()->instance('tenant', $tenant);
                    return $next($request);
                }
            }
            
            // If bypass is enabled but no valid domain, redirect to landing
            return redirect('/');
        }
        
        $host = $request->getHost();
        $baseDomain = config('app.domain', 'localhost');
        
        // Extract subdomain from host
        $subdomain = str_replace('.' . $baseDomain, '', $host);
        
        // If no subdomain (e.g., just localhost), redirect to landing
        if ($subdomain === $baseDomain) {
            return redirect('/');
        }
        
        $tenant = Tenant::where('domain', $subdomain)->first();
        
        if (!$tenant) {
            return redirect('/');
        }
        
        // Store tenant in the request for later use
        $request->merge(['tenant' => $tenant]);
        app()->instance('tenant', $tenant);
        
        return $next($request);
    }
}
