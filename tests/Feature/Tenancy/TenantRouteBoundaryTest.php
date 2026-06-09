<?php

namespace Tests\Feature\Tenancy;

use App\Http\Middleware\IdentifyTenant;
use Illuminate\Routing\Route;
use Tests\TestCase;

class TenantRouteBoundaryTest extends TestCase
{
    public function testTenantApiRoutesResolveATenantBeforeTheirControllerRuns(): void
    {
        $exceptions = [
            'api/biometric/events/{tenantDomain}',
        ];

        $unprotected = collect(app('router')->getRoutes())
            ->filter(fn (Route $route) => str_starts_with($route->uri(), 'api/'))
            ->reject(fn (Route $route) => in_array($route->uri(), $exceptions, true))
            ->reject(fn (Route $route) => in_array(IdentifyTenant::class, $route->gatherMiddleware(), true))
            ->map(fn (Route $route) => implode('|', $route->methods()) . ' ' . $route->uri())
            ->values()
            ->all();

        $this->assertSame([], $unprotected, 'Tenant API routes missing IdentifyTenant middleware.');
    }

    public function testTenantWebRoutesResolveATenantBeforeTheirControllerRuns(): void
    {
        $exceptions = [
            '/',
            'storage/{path}',
            'up',
        ];

        $unprotected = collect(app('router')->getRoutes())
            ->reject(fn (Route $route) => str_starts_with($route->uri(), 'api/'))
            ->reject(fn (Route $route) => in_array($route->uri(), $exceptions, true))
            ->reject(fn (Route $route) => in_array(IdentifyTenant::class, $route->gatherMiddleware(), true))
            ->map(fn (Route $route) => implode('|', $route->methods()) . ' ' . $route->uri())
            ->values()
            ->all();

        $this->assertSame([], $unprotected, 'Tenant web routes missing IdentifyTenant middleware.');
    }

    public function testBiometricWebhookIsTheOnlyApiRouteThatResolvesTenantInsideItsController(): void
    {
        $routes = collect(app('router')->getRoutes())
            ->filter(fn (Route $route) => str_starts_with($route->uri(), 'api/'))
            ->reject(fn (Route $route) => in_array(IdentifyTenant::class, $route->gatherMiddleware(), true))
            ->map(fn (Route $route) => $route->uri())
            ->values()
            ->all();

        $this->assertSame(['api/biometric/events/{tenantDomain}'], $routes);
    }
}
