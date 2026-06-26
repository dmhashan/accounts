<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConfigurationUrlParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateTenantDatabases extends Command
{
    protected $signature = 'tenants:migrate
                            {--database= : Run migrations only for one tenant database UUID}
                            {--subdomain= : Run migrations only for one tenant subdomain}
                            {--include-blank : Also run migrations for the blank tenant template database}
                            {--blank-database= : Override the blank tenant template database name}
                            {--path= : Override the tenant migrations path}
                            {--pretend : Dump the SQL queries that would be run}
                            {--step : Record each migration as a separate batch}
                            {--force : Required in production}';

    protected $description = 'Run migrations for all tenant databases and the blank onboarding database';

    public function handle(): int
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('Production execution requires --force.');

            return self::FAILURE;
        }

        if ($this->option('database') && $this->option('subdomain')) {
            $this->error('Use either --database or --subdomain, not both.');

            return self::FAILURE;
        }

        $originalDefaultConnection = DB::getDefaultConnection();

        try {
            $migrationPath = $this->migrationPath();

            if (!is_dir($this->absolutePath($migrationPath))) {
                $this->error("Tenant migration path [{$migrationPath}] does not exist.");

                return self::FAILURE;
            }

            if (!Schema::connection($this->centralConnection())->hasTable('tenants')) {
                $this->error('The central tenants registry table [tenants] does not exist.');

                return self::FAILURE;
            }

            $targets = $this->targets();

            if ($targets->isEmpty()) {
                $this->error('No tenant databases matched the requested criteria.');

                return self::FAILURE;
            }

            $this->info('Starting tenant database migrations.');

            foreach ($targets as $target) {
                $this->migrateTarget($target, $migrationPath);
            }

            $this->info('Tenant database migrations completed.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            DB::purge($this->tenantConnection());
            DB::setDefaultConnection($originalDefaultConnection);
        }
    }

    /**
     * @return Collection<int, object{subdomain: string, database_name: string, blank: bool}>
     */
    private function targets(): Collection
    {
        $query = DB::connection($this->centralConnection())
            ->table('tenants')
            ->select(['subdomain', 'database_name'])
            ->whereNotNull('database_name')
            ->where('database_name', '<>', '')
            ->orderBy('subdomain');

        if ($subdomain = $this->normaliseSubdomain($this->option('subdomain'))) {
            $query->where('subdomain', $subdomain);
        }

        $targets = $query->get()
            ->map(fn (object $tenant): object => (object) [
                'subdomain' => (string) $tenant->subdomain,
                'database_name' => (string) $tenant->database_name,
                'blank' => false,
            ]);

        if ($this->option('include-blank')) {
            $blankDatabase = $this->blankDatabase();
            $targets->push((object) [
                'subdomain' => '_blank',
                'database_name' => $blankDatabase,
                'blank' => true,
            ]);
        }

        if ($database = $this->option('database')) {
            $targets = $targets->filter(fn (object $target): bool => $target->database_name === (string) $database);
        }

        return $targets
            ->unique('database_name')
            ->values();
    }

    private function migrateTarget(object $target, string $migrationPath): void
    {
        $database = (string) $target->database_name;

        $this->assertSafeDatabaseName($database, (bool) $target->blank);

        if (!$this->databaseExists($database)) {
            throw new \RuntimeException("Tenant database [{$database}] does not exist.");
        }

        $this->configureTenantConnection($database);

        $label = $target->blank ? 'blank template' : "tenant [{$target->subdomain}]";
        $this->line('');
        $this->info("Migrating {$label} database [{$database}].");

        try {
            $exitCode = Artisan::call('migrate', [
                '--database' => $this->tenantConnection(),
                '--path' => [$migrationPath],
                '--force' => true,
                '--pretend' => (bool) $this->option('pretend'),
                '--step' => (bool) $this->option('step'),
                '--no-interaction' => true,
            ]);

            $this->line(Artisan::output());

            if ($exitCode !== self::SUCCESS) {
                throw new \RuntimeException("Migration command failed for database [{$database}] with exit code [{$exitCode}].");
            }

            $this->info("Success: {$database}");
        } catch (\Throwable $e) {
            throw new \RuntimeException("Failed migrating database [{$database}]: {$e->getMessage()}", previous: $e);
        }
    }

    private function configureTenantConnection(string $database): void
    {
        $central = (new ConfigurationUrlParser)->parseConfiguration(
            config('database.connections.' . $this->centralConnection()),
        );
        $central['database'] = $database;
        $central['url'] = null;

        config(['database.connections.' . $this->tenantConnection() => $central]);

        DB::purge($this->tenantConnection());
        DB::reconnect($this->tenantConnection());
    }

    private function databaseExists(string $database): bool
    {
        $connection = DB::connection($this->centralConnection());

        if (!in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            return true;
        }

        return $connection->selectOne(
            'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?',
            [$database],
        ) !== null;
    }

    private function migrationPath(): string
    {
        return trim((string) ($this->option('path') ?: config('tenancy.tenant_migrations_path', 'database/migrations/tenant')));
    }

    private function blankDatabase(): string
    {
        return trim((string) ($this->option('blank-database') ?: config('tenancy.blank_database', '_blank')));
    }

    private function absolutePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    private function centralConnection(): string
    {
        return (string) config('tenancy.central_connection', 'central');
    }

    private function tenantConnection(): string
    {
        return (string) config('tenancy.tenant_connection', 'tenant');
    }

    private function normaliseSubdomain(mixed $subdomain): ?string
    {
        if (!is_string($subdomain)) {
            return null;
        }

        $subdomain = strtolower(trim($subdomain, ". \t\n\r\0\x0B"));

        if ($subdomain === '') {
            return null;
        }

        if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $subdomain)) {
            throw new \RuntimeException("Unsafe tenant subdomain [{$subdomain}].");
        }

        return $subdomain;
    }

    private function assertSafeDatabaseName(string $database, bool $allowBlank): void
    {
        if ($allowBlank && $database === $this->blankDatabase()) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
                throw new \RuntimeException("Unsafe blank database name [{$database}].");
            }

            return;
        }

        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $database)) {
            throw new \RuntimeException("Unsafe tenant database name [{$database}]. Expected UUID database names.");
        }
    }
}
