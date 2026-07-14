<?php

namespace Tests\Feature\Tenancy;

use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\InitializeTenantConnection;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Route;
use Illuminate\Session\Middleware\StartSession;
use Tests\TestCase;

class TenantRouteBoundaryTest extends TestCase
{
    public function testTenantConnectionInitializesBeforeSessionAuthentication(): void
    {
        $kernel = app(Kernel::class);
        $web = $kernel->getMiddlewareGroups()['web'];
        $priority = $kernel->getMiddlewarePriority();

        $this->assertSame(InitializeTenantConnection::class, $web[0]);
        $this->assertLessThan(
            array_search(StartSession::class, $priority, true),
            array_search(InitializeTenantConnection::class, $priority, true),
        );
    }

    public function testTenantApiRoutesResolveATenantBeforeTheirControllerRuns(): void
    {
        $exceptions = [
            'api/biometric/events/{tenantDomain}',
        ];

        $unprotected = collect(app('router')->getRoutes())
            ->filter(fn (Route $route) => str_starts_with($route->uri(), 'api/'))
            ->reject(fn (Route $route) => str_starts_with($route->uri(), 'api/portal/'))
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
            ->reject(fn (Route $route) => str_starts_with($route->uri(), 'api/portal/'))
            ->reject(fn (Route $route) => in_array(IdentifyTenant::class, $route->gatherMiddleware(), true))
            ->map(fn (Route $route) => $route->uri())
            ->values()
            ->all();

        $this->assertSame(['api/biometric/events/{tenantDomain}'], $routes);
    }
}
