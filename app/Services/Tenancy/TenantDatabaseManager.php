<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConfigurationUrlParser;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TenantDatabaseManager
{
    public function __construct(private readonly Application $app) {}

    public function isolationEnabled(): bool
    {
        return (bool) config('tenancy.database_isolation_enabled', false);
    }

    public function domainForRequest(Request $request): ?string
    {
        if (!config('app.multitenancy_enabled', true)) {
            return $this->normaliseDomain((string) config('app.multitenancy_bypass_domain'));
        }

        $host = strtolower($request->getHost());
        $baseDomain = strtolower(trim((string) config('app.domain', 'localhost'), '.'));

        if ($host === $baseDomain || !str_ends_with($host, '.' . $baseDomain)) {
            return null;
        }

        return $this->normaliseDomain(substr($host, 0, -strlen('.' . $baseDomain)));
    }

    public function activateByDomain(string $domain): ?Tenant
    {
        $domain = $this->normaliseDomain($domain);

        if ($domain === null) {
            return null;
        }

        if (!$this->isolationEnabled()) {
            $tenant = Tenant::where('domain', $domain)->first();

            return $tenant ? $this->bind($tenant) : null;
        }

        $this->assertHostOnlySessions();
        $this->deactivate();

        $mapping = $this->mappings()->firstWhere('subdomain', $domain);

        if (!$mapping) {
            return null;
        }

        $database = (string) $mapping->database_name;
        $this->assertUuidDatabaseName($database);
        $this->activateDatabase($database);

        $tenant = Tenant::on($this->tenantConnection())
            ->where('domain', $domain)
            ->where('tenant_uuid', $database)
            ->first();

        if (!$tenant) {
            $this->deactivate();

            throw new \RuntimeException("Tenant database [{$database}] does not contain its expected tenant row.");
        }

        return $this->bind($tenant);
    }

    public function activateById(int $tenantId): ?Tenant
    {
        if ($tenantId <= 0) {
            return null;
        }

        if (!$this->isolationEnabled()) {
            $tenant = Tenant::find($tenantId);

            return $tenant ? $this->bind($tenant) : null;
        }

        if ($this->app->bound('tenant')) {
            $current = $this->app->make('tenant');

            if ($current instanceof Tenant && (int) $current->getKey() === $tenantId) {
                return $current;
            }
        }

        foreach ($this->mappings() as $mapping) {
            $tenant = $this->activateByDomain((string) $mapping->subdomain);

            if ($tenant && (int) $tenant->getKey() === $tenantId) {
                return $tenant;
            }
        }

        $this->deactivate();

        return null;
    }

    /**
     * @return Collection<int, object{subdomain: string, database_name: string}>
     */
    public function mappings(): Collection
    {
        if (!$this->isolationEnabled()) {
            return Tenant::query()
                ->orderBy('domain')
                ->get(['domain', 'tenant_uuid'])
                ->map(fn (Tenant $tenant): object => (object) [
                    'subdomain' => $tenant->domain,
                    'database_name' => $tenant->tenant_uuid,
                ]);
        }

        return DB::connection($this->centralConnection())
            ->table('tenants')
            ->orderBy('subdomain')
            ->get(['subdomain', 'database_name']);
    }

    public function eachTenant(callable $callback): void
    {
        foreach ($this->mappings() as $mapping) {
            $tenant = $this->activateByDomain((string) $mapping->subdomain);

            if ($tenant) {
                $callback($tenant);
            }
        }

        $this->deactivate();
    }

    public function deactivate(): void
    {
        $this->app->forgetInstance('tenant');

        if (!$this->isolationEnabled()) {
            return;
        }

        DB::purge($this->tenantConnection());
        DB::setDefaultConnection($this->centralConnection());
    }

    public function currentDomain(): ?string
    {
        if (!$this->app->bound('tenant')) {
            return null;
        }

        $tenant = $this->app->make('tenant');

        return $tenant instanceof Tenant ? $tenant->domain : null;
    }

    private function activateDatabase(string $database): void
    {
        $central = (new ConfigurationUrlParser)->parseConfiguration(
            config('database.connections.' . $this->centralConnection()),
        );
        $central['database'] = $database;
        $central['url'] = null;

        config(['database.connections.' . $this->tenantConnection() => $central]);
        DB::purge($this->tenantConnection());
        DB::setDefaultConnection($this->tenantConnection());
        DB::reconnect($this->tenantConnection());
    }

    private function bind(Tenant $tenant): Tenant
    {
        $this->app->instance('tenant', $tenant);

        return $tenant;
    }

    private function centralConnection(): string
    {
        return (string) config('tenancy.central_connection', 'central');
    }

    private function tenantConnection(): string
    {
        return (string) config('tenancy.tenant_connection', 'tenant');
    }

    private function normaliseDomain(string $domain): ?string
    {
        $domain = strtolower(trim($domain, ". \t\n\r\0\x0B"));

        if ($domain === '' || !preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $domain)) {
            return null;
        }

        return $domain;
    }

    private function assertUuidDatabaseName(string $database): void
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $database)) {
            throw new \RuntimeException("Unsafe tenant database name [{$database}].");
        }
    }

    private function assertHostOnlySessions(): void
    {
        $sessionDomain = config('session.domain');

        if ($sessionDomain !== null && $sessionDomain !== '') {
            throw new \RuntimeException('Database isolation requires host-only session cookies. Set SESSION_DOMAIN=null.');
        }
    }
}
