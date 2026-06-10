<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SplitTenantDatabasesOnceCommand extends Command
{
    private const INFRASTRUCTURE_TABLES = [
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'jobs',
        'password_reset_tokens',
        'personal_access_tokens',
        'sessions',
    ];

    private const SHARED_REFERENCE_TABLES = [
        'migrations',
        'permissions',
        'roles',
        'role_permission',
    ];

    protected $signature = 'tenants:split-once
        {--central=portal : New central registry database name}
        {--execute : Create databases and copy data; without this option only preflight runs}
        {--delete-source : Delete copied tenant-owned rows from the source after verification}
        {--backup-confirmed : Confirm a restorable source backup exists}
        {--maintenance-confirmed : Confirm all source writes are stopped}
        {--acknowledge-runtime-break : Confirm deleting source rows will break the current runtime}
        {--force : Required for production execution and skips confirmation prompts}';

    protected $description = 'One-time split of the current MySQL database into a minimal central registry and UUID-named tenant databases';

    private \PDO $pdo;

    private string $sourceDatabase;

    private string $centralDatabase;

    /** @var string[] */
    private array $tables = [];

    /** @var string[] */
    private array $directTenantTables = [];

    /** @var array<string, string[]> */
    private array $primaryKeys = [];

    /**
     * @var array<int, array{
     *     child_table: string,
     *     child_column: string,
     *     parent_table: string,
     *     parent_column: string
     * }>
     */
    private array $foreignKeys = [];

    /** @var array<int, array{id: int, domain: string, tenant_uuid: string, database_name: string}> */
    private array $tenants = [];

    /** @var string[] */
    private array $createdDatabases = [];

    public function handle(): int
    {
        try {
            $this->initialise();
            $this->preflight();
            $this->printPlan();

            if (!$this->option('execute')) {
                $this->info('Preflight passed. No databases or rows were changed.');
                $this->line('Run again with --execute after reviewing the plan and taking a backup.');

                return self::SUCCESS;
            }

            if (!$this->confirmExecution()) {
                $this->info('Aborted. No databases or rows were changed.');

                return self::SUCCESS;
            }

            $this->createDatabasesAndSchemas();
            $this->copyAndVerify();

            if ($this->option('delete-source')) {
                $this->deleteCopiedSourceRows();
            }

            $this->info('Tenant database split completed and verified.');
            $this->warn('This command is intentionally one-time. Running it again will be refused.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->restoreSourceDatabase();
            $this->cleanupCreatedDatabases();
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function initialise(): void
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'mysql') {
            throw new \RuntimeException('This one-time command only supports MySQL.');
        }

        $this->pdo = $connection->getPdo();
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->sourceDatabase = $connection->getDatabaseName();
        $this->centralDatabase = trim((string) $this->option('central'));

        $this->assertDatabaseName($this->sourceDatabase);
        $this->assertDatabaseName($this->centralDatabase);

        if ($this->sourceDatabase === $this->centralDatabase) {
            throw new \RuntimeException('The central database name must differ from the source database.');
        }

        $this->tables = $this->loadTables();
        $this->directTenantTables = $this->loadDirectTenantTables();
        $this->primaryKeys = $this->loadPrimaryKeys();
        $this->foreignKeys = $this->loadForeignKeys();
        $this->tenants = $this->loadTenants();
    }

    private function preflight(): void
    {
        if ($this->databaseExists($this->centralDatabase)) {
            throw new \RuntimeException(
                "Central database [{$this->centralDatabase}] already exists. Refusing to rerun a one-time command.",
            );
        }

        if ($this->tenants === []) {
            throw new \RuntimeException('The source tenants table is empty.');
        }

        foreach ($this->tenants as $tenant) {
            if ($this->databaseExists($tenant['database_name'])) {
                throw new \RuntimeException(
                    "Tenant database [{$tenant['database_name']}] already exists. Refusing to overwrite it.",
                );
            }
        }

        $viewCount = $this->scalar(
            'SELECT COUNT(*) FROM information_schema.views WHERE table_schema = ?',
            [$this->sourceDatabase],
        );

        if ($viewCount !== 0) {
            throw new \RuntimeException('The source contains views. This command intentionally handles base tables only.');
        }

        foreach ($this->directTenantTables as $table) {
            $orphanCount = $this->scalar(
                'SELECT COUNT(*) FROM ' . $this->qualified($this->sourceDatabase, $table) . ' AS owned
                 LEFT JOIN ' . $this->qualified($this->sourceDatabase, 'tenants') . ' AS tenant
                    ON tenant.id = owned.tenant_id
                 WHERE owned.tenant_id IS NULL OR tenant.id IS NULL',
            );

            if ($orphanCount !== 0) {
                throw new \RuntimeException(
                    "Table [{$table}] contains {$orphanCount} row(s) without a valid tenant. Resolve them before splitting.",
                );
            }
        }

        foreach ($this->foreignKeys as $foreignKey) {
            $orphans = $this->foreignKeyOrphanCount($this->sourceDatabase, $foreignKey);

            if ($orphans !== 0) {
                throw new \RuntimeException(
                    "Source foreign key [{$foreignKey['child_table']}.{$foreignKey['child_column']}] has {$orphans} orphan(s).",
                );
            }
        }

        foreach ($this->unknownOwnershipTables() as $table) {
            $count = $this->tableCount($this->sourceDatabase, $table);

            if ($count !== 0) {
                throw new \RuntimeException(
                    "Table [{$table}] contains {$count} row(s), but ownership cannot be derived from tenant_id or foreign keys.",
                );
            }
        }

        foreach ($this->copyableBusinessTables() as $table) {
            if (($this->primaryKeys[$table] ?? []) === [] && $this->tableCount($this->sourceDatabase, $table) !== 0) {
                throw new \RuntimeException(
                    "Table [{$table}] contains data but has no primary key, so it cannot be safely verified or moved.",
                );
            }
        }

        if ($this->option('delete-source')) {
            foreach (['backup-confirmed', 'maintenance-confirmed', 'acknowledge-runtime-break', 'force'] as $required) {
                if (!$this->option($required)) {
                    throw new \RuntimeException("--delete-source requires --{$required}.");
                }
            }
        }

        if (app()->environment('production') && $this->option('execute') && !$this->option('force')) {
            throw new \RuntimeException('Production execution requires --force.');
        }
    }

    private function printPlan(): void
    {
        $this->newLine();
        $this->info('One-time tenant split plan');
        $this->table(['Item', 'Value'], [
            ['Source database', $this->sourceDatabase],
            ['Central database', $this->centralDatabase],
            ['Central tables', 'tenants only: subdomain, database_name'],
            ['Tenant databases', (string) count($this->tenants)],
            ['Tenant database naming', 'exact tenant UUID'],
            ['Source deletion', $this->option('delete-source') ? 'ENABLED after verification' : 'disabled; source remains unchanged'],
        ]);

        $rows = [];

        foreach ($this->tenants as $tenant) {
            $ownedRows = 1;

            foreach ($this->directTenantTables as $table) {
                $ownedRows += $this->scalar(
                    'SELECT COUNT(*) FROM ' . $this->qualified($this->sourceDatabase, $table) . ' WHERE tenant_id = ?',
                    [$tenant['id']],
                );
            }

            $rows[] = [
                (string) $tenant['id'],
                $tenant['domain'],
                $tenant['database_name'],
                (string) $ownedRows,
            ];
        }

        $this->table(['Tenant ID', 'Subdomain', 'Database', 'Direct rows + tenant row'], $rows);
        $this->line('Shared reference data copied to every tenant: ' . implode(', ', self::SHARED_REFERENCE_TABLES));
        $this->line('Operational infrastructure kept empty in tenants: ' . implode(', ', self::INFRASTRUCTURE_TABLES));
    }

    private function confirmExecution(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        return $this->confirm(
            'Create the central and tenant databases and copy the planned data?',
            false,
        );
    }

    private function createDatabasesAndSchemas(): void
    {
        [$charset, $collation] = $this->sourceCharsetAndCollation();

        $this->createDatabase($this->centralDatabase, $charset, $collation);
        $this->createdDatabases[] = $this->centralDatabase;

        $this->pdo->exec('USE ' . $this->identifier($this->centralDatabase));
        $this->pdo->exec(
            'CREATE TABLE ' . $this->identifier('tenants') . ' (
                subdomain VARCHAR(255) NOT NULL,
                database_name VARCHAR(64) NOT NULL,
                PRIMARY KEY (subdomain),
                UNIQUE KEY tenants_database_name_unique (database_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ' COLLATE=' . $collation,
        );

        foreach ($this->tenants as $tenant) {
            $database = $tenant['database_name'];

            $this->createDatabase($database, $charset, $collation);
            $this->createdDatabases[] = $database;
            $this->createTenantSchema($database);
            $this->line("Created schema [{$database}] for [{$tenant['domain']}].");
        }

        $this->restoreSourceDatabase();
    }

    private function createDatabase(string $database, string $charset, string $collation): void
    {
        $this->pdo->exec(
            'CREATE DATABASE ' . $this->identifier($database) . ' CHARACTER SET ' . $charset . ' COLLATE ' . $collation,
        );
    }

    private function createTenantSchema(string $database): void
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $this->pdo->exec('USE ' . $this->identifier($database));

        try {
            foreach ($this->tables as $table) {
                $statement = $this->showCreateTable($table);
                $this->pdo->exec($statement);
            }
        } finally {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            $this->restoreSourceDatabase();
        }
    }

    private function copyAndVerify(): void
    {
        $this->pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $this->pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');

        try {
            $portalInsert = $this->pdo->prepare(
                'INSERT INTO ' . $this->qualified($this->centralDatabase, 'tenants') . ' (subdomain, database_name)
                 VALUES (?, ?)',
            );

            foreach ($this->tenants as $tenant) {
                $portalInsert->execute([$tenant['domain'], $tenant['database_name']]);
                $this->copyTenant($tenant);
                $this->verifyTenant($tenant);
                $this->info("Copied and verified [{$tenant['domain']}].");
            }

            $this->verifyPortal();
            $this->verifyNoTenantOverlap();
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        } finally {
            $this->restoreSourceDatabase();
        }
    }

    /**
     * @param  array{id: int, domain: string, tenant_uuid: string, database_name: string}  $tenant
     */
    private function copyTenant(array $tenant): void
    {
        $database = $tenant['database_name'];
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        try {
            $this->insertSelect(
                $database,
                'tenants',
                'SELECT * FROM ' . $this->qualified($this->sourceDatabase, 'tenants') . ' WHERE id = ?',
                [$tenant['id']],
            );

            foreach ($this->directTenantTables as $table) {
                $this->insertSelect(
                    $database,
                    $table,
                    'SELECT * FROM ' . $this->qualified($this->sourceDatabase, $table) . ' WHERE tenant_id = ?',
                    [$tenant['id']],
                );
            }

            $this->copyDependentRows($database);

            foreach (self::SHARED_REFERENCE_TABLES as $table) {
                if (in_array($table, $this->tables, true)) {
                    $this->insertSelect(
                        $database,
                        $table,
                        'SELECT * FROM ' . $this->qualified($this->sourceDatabase, $table),
                    );
                }
            }
        } finally {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function copyDependentRows(string $database): void
    {
        $candidates = array_values(array_diff(
            $this->tables,
            $this->directTenantTables,
            self::SHARED_REFERENCE_TABLES,
            self::INFRASTRUCTURE_TABLES,
            ['tenants'],
        ));

        for ($pass = 0; $pass < count($this->tables); $pass++) {
            $inserted = 0;

            foreach ($candidates as $table) {
                $foreignKeys = array_values(array_filter(
                    $this->foreignKeys,
                    fn (array $foreignKey): bool => $foreignKey['child_table'] === $table
                        && !in_array($foreignKey['parent_table'], self::SHARED_REFERENCE_TABLES, true)
                        && !in_array($foreignKey['parent_table'], self::INFRASTRUCTURE_TABLES, true),
                ));

                if ($foreignKeys === []) {
                    continue;
                }

                $positive = [];
                $required = [];

                foreach ($foreignKeys as $foreignKey) {
                    $exists = 'EXISTS (
                        SELECT 1 FROM ' . $this->qualified($database, $foreignKey['parent_table']) . ' AS parent
                        WHERE parent.' . $this->identifier($foreignKey['parent_column']) . '
                            = child.' . $this->identifier($foreignKey['child_column']) . '
                    )';

                    $positive[] = '(child.' . $this->identifier($foreignKey['child_column']) . ' IS NOT NULL AND ' . $exists . ')';
                    $required[] = '(child.' . $this->identifier($foreignKey['child_column']) . ' IS NULL OR ' . $exists . ')';
                }

                $notExists = $this->notExistsByPrimaryKey($database, $table, 'child');
                $sql = 'SELECT child.* FROM ' . $this->qualified($this->sourceDatabase, $table) . ' AS child
                    WHERE (' . implode(' OR ', $positive) . ')
                    AND ' . implode(' AND ', $required) . '
                    AND ' . $notExists;

                $inserted += $this->insertSelect($database, $table, $sql);
            }

            if ($inserted === 0) {
                return;
            }
        }

        throw new \RuntimeException("Dependent-row discovery did not converge for database [{$database}].");
    }

    private function insertSelect(string $database, string $table, string $select, array $bindings = []): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO ' . $this->qualified($database, $table) . ' ' . $select,
        );
        $statement->execute($bindings);

        return $statement->rowCount();
    }

    /**
     * @param  array{id: int, domain: string, tenant_uuid: string, database_name: string}  $tenant
     */
    private function verifyTenant(array $tenant): void
    {
        $database = $tenant['database_name'];

        if ($this->tableCount($database, 'tenants') !== 1) {
            throw new \RuntimeException("Tenant database [{$database}] does not contain exactly one tenant row.");
        }

        $tenantId = $this->scalar(
            'SELECT id FROM ' . $this->qualified($database, 'tenants') . ' LIMIT 1',
        );

        if ($tenantId !== $tenant['id']) {
            throw new \RuntimeException("Tenant database [{$database}] contains the wrong tenant row.");
        }

        foreach ($this->directTenantTables as $table) {
            $sourceCount = $this->scalar(
                'SELECT COUNT(*) FROM ' . $this->qualified($this->sourceDatabase, $table) . ' WHERE tenant_id = ?',
                [$tenant['id']],
            );
            $targetCount = $this->tableCount($database, $table);
            $foreignCount = $this->scalar(
                'SELECT COUNT(*) FROM ' . $this->qualified($database, $table) . ' WHERE tenant_id <> ? OR tenant_id IS NULL',
                [$tenant['id']],
            );

            if ($sourceCount !== $targetCount || $foreignCount !== 0) {
                throw new \RuntimeException(
                    "Tenant verification failed for [{$database}.{$table}] (source={$sourceCount}, target={$targetCount}, foreign={$foreignCount}).",
                );
            }
        }

        foreach (self::SHARED_REFERENCE_TABLES as $table) {
            if (!in_array($table, $this->tables, true)) {
                continue;
            }

            $sourceCount = $this->tableCount($this->sourceDatabase, $table);
            $targetCount = $this->tableCount($database, $table);

            if ($sourceCount !== $targetCount) {
                throw new \RuntimeException(
                    "Shared table verification failed for [{$database}.{$table}] (source={$sourceCount}, target={$targetCount}).",
                );
            }
        }

        foreach (self::INFRASTRUCTURE_TABLES as $table) {
            if (in_array($table, $this->tables, true) && $this->tableCount($database, $table) !== 0) {
                throw new \RuntimeException("Infrastructure table [{$database}.{$table}] must remain empty.");
            }
        }

        foreach ($this->foreignKeys as $foreignKey) {
            $orphans = $this->foreignKeyOrphanCount($database, $foreignKey);

            if ($orphans !== 0) {
                throw new \RuntimeException(
                    "Foreign-key verification failed for [{$database}.{$foreignKey['child_table']}.{$foreignKey['child_column']}] with {$orphans} orphan(s).",
                );
            }
        }
    }

    private function verifyPortal(): void
    {
        $count = $this->tableCount($this->centralDatabase, 'tenants');

        if ($count !== count($this->tenants)) {
            throw new \RuntimeException(
                'Central registry verification failed (expected=' . count($this->tenants) . ", actual={$count}).",
            );
        }
    }

    private function verifyNoTenantOverlap(): void
    {
        $tables = $this->copyableBusinessTables();

        for ($left = 0; $left < count($this->tenants); $left++) {
            for ($right = $left + 1; $right < count($this->tenants); $right++) {
                $leftDatabase = $this->tenants[$left]['database_name'];
                $rightDatabase = $this->tenants[$right]['database_name'];

                foreach ($tables as $table) {
                    $primaryKey = $this->primaryKeys[$table] ?? [];

                    if ($primaryKey === []) {
                        continue;
                    }

                    $join = implode(' AND ', array_map(
                        fn (string $column): string => 'left_rows.' . $this->identifier($column)
                            . ' = right_rows.' . $this->identifier($column),
                        $primaryKey,
                    ));
                    $overlap = $this->scalar(
                        'SELECT COUNT(*) FROM ' . $this->qualified($leftDatabase, $table) . ' AS left_rows
                         INNER JOIN ' . $this->qualified($rightDatabase, $table) . ' AS right_rows ON ' . $join,
                    );

                    if ($overlap !== 0) {
                        throw new \RuntimeException(
                            "Tenant databases [{$leftDatabase}] and [{$rightDatabase}] overlap in [{$table}] by {$overlap} row(s).",
                        );
                    }
                }
            }
        }
    }

    private function deleteCopiedSourceRows(): void
    {
        $this->warn('Deleting copied tenant-owned rows from the source database.');
        $this->pdo->beginTransaction();
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        try {
            $tables = array_values(array_diff($this->copyableBusinessTables(), ['tenants']));

            foreach ($this->tenants as $tenant) {
                $database = $tenant['database_name'];

                foreach ($tables as $table) {
                    $this->deleteSourceRowsPresentInTarget($database, $table);
                }

                $this->deleteSourceRowsPresentInTarget($database, 'tenants');
            }

            foreach ($this->tenants as $tenant) {
                foreach ($this->copyableBusinessTables() as $table) {
                    if ($this->matchingSourceTargetCount($tenant['database_name'], $table) !== 0) {
                        throw new \RuntimeException(
                            "Source deletion verification failed for [{$tenant['database_name']}.{$table}].",
                        );
                    }
                }
            }

            foreach ($this->foreignKeys as $foreignKey) {
                $orphans = $this->foreignKeyOrphanCount($this->sourceDatabase, $foreignKey);

                if ($orphans !== 0) {
                    throw new \RuntimeException(
                        "Source deletion would leave {$orphans} orphan(s) at [{$foreignKey['child_table']}.{$foreignKey['child_column']}].",
                    );
                }
            }

            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            $this->pdo->commit();
            $this->warn('Source tenant-owned rows were deleted after copy verification.');
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');

            throw $e;
        }
    }

    private function deleteSourceRowsPresentInTarget(string $targetDatabase, string $table): void
    {
        $primaryKey = $this->primaryKeys[$table] ?? [];

        if ($primaryKey === []) {
            if ($this->tableCount($targetDatabase, $table) !== 0) {
                throw new \RuntimeException("Cannot delete source rows for [{$table}] because it has no primary key.");
            }

            return;
        }

        $join = implode(' AND ', array_map(
            fn (string $column): string => 'source_rows.' . $this->identifier($column)
                . ' = target_rows.' . $this->identifier($column),
            $primaryKey,
        ));

        $this->pdo->exec(
            'DELETE source_rows FROM ' . $this->qualified($this->sourceDatabase, $table) . ' AS source_rows
             INNER JOIN ' . $this->qualified($targetDatabase, $table) . ' AS target_rows ON ' . $join,
        );
    }

    private function matchingSourceTargetCount(string $targetDatabase, string $table): int
    {
        $primaryKey = $this->primaryKeys[$table] ?? [];

        if ($primaryKey === []) {
            return 0;
        }

        $join = implode(' AND ', array_map(
            fn (string $column): string => 'source_rows.' . $this->identifier($column)
                . ' = target_rows.' . $this->identifier($column),
            $primaryKey,
        ));

        return $this->scalar(
            'SELECT COUNT(*) FROM ' . $this->qualified($this->sourceDatabase, $table) . ' AS source_rows
             INNER JOIN ' . $this->qualified($targetDatabase, $table) . ' AS target_rows ON ' . $join,
        );
    }

    /**
     * @param  array{
     *     child_table: string,
     *     child_column: string,
     *     parent_table: string,
     *     parent_column: string
     * }  $foreignKey
     */
    private function foreignKeyOrphanCount(string $database, array $foreignKey): int
    {
        return $this->scalar(
            'SELECT COUNT(*)
             FROM ' . $this->qualified($database, $foreignKey['child_table']) . ' AS child
             LEFT JOIN ' . $this->qualified($database, $foreignKey['parent_table']) . ' AS parent
                ON parent.' . $this->identifier($foreignKey['parent_column']) . '
                    = child.' . $this->identifier($foreignKey['child_column']) . '
             WHERE child.' . $this->identifier($foreignKey['child_column']) . ' IS NOT NULL
                AND parent.' . $this->identifier($foreignKey['parent_column']) . ' IS NULL',
        );
    }

    /** @return string[] */
    private function loadTables(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT table_name AS table_name
             FROM information_schema.tables
             WHERE table_schema = ? AND table_type = "BASE TABLE"
             ORDER BY table_name',
        );
        $statement->execute([$this->sourceDatabase]);

        return array_map('strval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** @return string[] */
    private function loadDirectTenantTables(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT table_name AS table_name
             FROM information_schema.columns
             WHERE table_schema = ? AND column_name = "tenant_id"
             ORDER BY table_name',
        );
        $statement->execute([$this->sourceDatabase]);

        return array_map('strval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** @return array<string, string[]> */
    private function loadPrimaryKeys(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT table_name AS table_name, column_name AS column_name
             FROM information_schema.key_column_usage
             WHERE table_schema = ? AND constraint_name = "PRIMARY"
             ORDER BY table_name, ordinal_position',
        );
        $statement->execute([$this->sourceDatabase]);
        $keys = [];

        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $keys[(string) $row['table_name']][] = (string) $row['column_name'];
        }

        return $keys;
    }

    /**
     * @return array<int, array{
     *     child_table: string,
     *     child_column: string,
     *     parent_table: string,
     *     parent_column: string
     * }>
     */
    private function loadForeignKeys(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                table_name AS child_table,
                column_name AS child_column,
                referenced_table_name AS parent_table,
                referenced_column_name AS parent_column
             FROM information_schema.key_column_usage
             WHERE table_schema = ? AND referenced_table_name IS NOT NULL
             ORDER BY table_name, ordinal_position',
        );
        $statement->execute([$this->sourceDatabase]);

        return array_map(
            fn (array $row): array => [
                'child_table' => (string) $row['child_table'],
                'child_column' => (string) $row['child_column'],
                'parent_table' => (string) $row['parent_table'],
                'parent_column' => (string) $row['parent_column'],
            ],
            $statement->fetchAll(\PDO::FETCH_ASSOC),
        );
    }

    /** @return array<int, array{id: int, domain: string, tenant_uuid: string, database_name: string}> */
    private function loadTenants(): array
    {
        foreach (['id', 'domain', 'tenant_uuid'] as $column) {
            $exists = $this->scalar(
                'SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = ? AND table_name = "tenants" AND column_name = ?',
                [$this->sourceDatabase, $column],
            );

            if ($exists !== 1) {
                throw new \RuntimeException("Source tenants table must contain [{$column}].");
            }
        }

        $statement = $this->pdo->query(
            'SELECT id, domain, tenant_uuid FROM ' . $this->qualified($this->sourceDatabase, 'tenants') . ' ORDER BY id',
        );
        $tenants = [];
        $domains = [];
        $databases = [];

        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $domain = trim((string) $row['domain']);
            $uuid = strtolower(trim((string) $row['tenant_uuid']));

            if ($domain === '') {
                throw new \RuntimeException("Tenant [{$row['id']}] has an empty subdomain.");
            }

            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid)) {
                throw new \RuntimeException("Tenant [{$row['id']}] has invalid UUID [{$uuid}].");
            }

            $this->assertDatabaseName($uuid);

            if (isset($domains[$domain]) || isset($databases[$uuid])) {
                throw new \RuntimeException('Tenant subdomains and UUIDs must be unique.');
            }

            $domains[$domain] = true;
            $databases[$uuid] = true;
            $tenants[] = [
                'id' => (int) $row['id'],
                'domain' => $domain,
                'tenant_uuid' => $uuid,
                'database_name' => $uuid,
            ];
        }

        return $tenants;
    }

    /** @return string[] */
    private function unknownOwnershipTables(): array
    {
        $reachable = array_fill_keys(array_merge($this->directTenantTables, ['tenants']), true);

        for ($pass = 0; $pass < count($this->tables); $pass++) {
            $changed = false;

            foreach ($this->foreignKeys as $foreignKey) {
                if (isset($reachable[$foreignKey['parent_table']]) && !isset($reachable[$foreignKey['child_table']])) {
                    $reachable[$foreignKey['child_table']] = true;
                    $changed = true;
                }
            }

            if (!$changed) {
                break;
            }
        }

        return array_values(array_filter(
            $this->tables,
            fn (string $table): bool => !isset($reachable[$table])
                && !in_array($table, self::SHARED_REFERENCE_TABLES, true)
                && !in_array($table, self::INFRASTRUCTURE_TABLES, true),
        ));
    }

    /** @return string[] */
    private function copyableBusinessTables(): array
    {
        return array_values(array_diff(
            $this->tables,
            self::SHARED_REFERENCE_TABLES,
            self::INFRASTRUCTURE_TABLES,
        ));
    }

    private function notExistsByPrimaryKey(string $database, string $table, string $sourceAlias): string
    {
        $primaryKey = $this->primaryKeys[$table] ?? [];

        if ($primaryKey === []) {
            throw new \RuntimeException("Table [{$table}] has no primary key.");
        }

        $conditions = implode(' AND ', array_map(
            fn (string $column): string => 'existing.' . $this->identifier($column)
                . ' = ' . $sourceAlias . '.' . $this->identifier($column),
            $primaryKey,
        ));

        return 'NOT EXISTS (
            SELECT 1 FROM ' . $this->qualified($database, $table) . ' AS existing
            WHERE ' . $conditions . '
        )';
    }

    private function showCreateTable(string $table): string
    {
        $statement = $this->pdo->query(
            'SHOW CREATE TABLE ' . $this->qualified($this->sourceDatabase, $table),
        );
        $row = $statement->fetch(\PDO::FETCH_NUM);

        if (!is_array($row) || !isset($row[1])) {
            throw new \RuntimeException("Unable to read schema for [{$table}].");
        }

        return (string) $row[1];
    }

    /** @return array{0: string, 1: string} */
    private function sourceCharsetAndCollation(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                default_character_set_name AS default_character_set_name,
                default_collation_name AS default_collation_name
             FROM information_schema.schemata WHERE schema_name = ?',
        );
        $statement->execute([$this->sourceDatabase]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            throw new \RuntimeException("Source database [{$this->sourceDatabase}] was not found.");
        }

        return [(string) $row['default_character_set_name'], (string) $row['default_collation_name']];
    }

    private function databaseExists(string $database): bool
    {
        return $this->scalar(
            'SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = ?',
            [$database],
        ) === 1;
    }

    private function tableCount(string $database, string $table): int
    {
        return $this->scalar(
            'SELECT COUNT(*) FROM ' . $this->qualified($database, $table),
        );
    }

    private function scalar(string $sql, array $bindings = []): int
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings);

        return (int) $statement->fetchColumn();
    }

    private function qualified(string $database, string $table): string
    {
        return $this->identifier($database) . '.' . $this->identifier($table);
    }

    private function identifier(string $value): string
    {
        if ($value === '' || strlen($value) > 64 || preg_match('/[`[:cntrl:]]/', $value)) {
            throw new \RuntimeException("Unsafe MySQL identifier [{$value}].");
        }

        return '`' . $value . '`';
    }

    private function assertDatabaseName(string $database): void
    {
        $this->identifier($database);
    }

    private function restoreSourceDatabase(): void
    {
        if (isset($this->pdo, $this->sourceDatabase)) {
            $this->pdo->exec('USE ' . $this->identifier($this->sourceDatabase));
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function cleanupCreatedDatabases(): void
    {
        if (!isset($this->pdo) || $this->createdDatabases === []) {
            return;
        }

        foreach (array_reverse($this->createdDatabases) as $database) {
            try {
                $this->pdo->exec('DROP DATABASE IF EXISTS ' . $this->identifier($database));
            } catch (\Throwable) {
                $this->warn("Cleanup failed for database [{$database}]. Remove it manually.");
            }
        }

        $this->createdDatabases = [];
    }
}
