<?php

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $database = $connection->getDatabaseName();

            foreach ($this->tablesWithTenantId($connection, $database) as $table) {
                $this->dropTenantIdFromTable($connection, $database, $table);
            }

            return;
        }

        if ($driver === 'sqlite') {
            $this->dropTenantIdFromSqliteDatabase($connection);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Dropping tenant_id columns is not reversible because historical tenant ownership values are removed.');
    }

    private function dropTenantIdFromSqliteDatabase(Connection $connection): void
    {
        $foreignKeys = (int) $connection->selectOne('PRAGMA foreign_keys')->foreign_keys;

        $connection->statement('PRAGMA foreign_keys = OFF');

        try {
            foreach ($this->sqliteTablesWithTenantId($connection) as $table) {
                $this->dropTenantIdFromSqliteTable($connection, $table);
            }
        } finally {
            $connection->statement('PRAGMA foreign_keys = ' . ($foreignKeys === 1 ? 'ON' : 'OFF'));
        }
    }

    /**
     * @return array<int, string>
     */
    private function sqliteTablesWithTenantId(Connection $connection): array
    {
        $tables = [];

        foreach ($connection->select(
            <<<'SQL'
            SELECT name
              FROM sqlite_master
             WHERE type = 'table'
               AND name NOT LIKE 'sqlite_%'
               AND name <> 'migrations'
             ORDER BY name
            SQL,
        ) as $row) {
            $table = (string) $row->name;

            foreach ($this->sqliteColumns($connection, $table) as $column) {
                if ($column === 'tenant_id') {
                    $tables[] = $table;
                    break;
                }
            }
        }

        return $tables;
    }

    private function dropTenantIdFromSqliteTable(Connection $connection, string $table): void
    {
        $createSql = $connection->selectOne(
            'SELECT sql FROM sqlite_master WHERE type = ? AND name = ?',
            ['table', $table],
        )?->sql;

        if (!is_string($createSql) || trim($createSql) === '') {
            return;
        }

        $indexes = $this->sqliteIndexes($connection, $table);

        foreach ($indexes as $index) {
            if (($index['origin'] ?? '') === 'pk' && $this->indexContainsTenantId($index)) {
                throw new RuntimeException("Cannot drop tenant_id from [{$table}] because it participates in the primary key.");
            }
        }

        $temporary = '__drop_tenant_id_' . $table;
        $columns = array_values(array_filter(
            $this->sqliteColumns($connection, $table),
            fn (string $column): bool => $column !== 'tenant_id',
        ));

        if ($columns === []) {
            throw new RuntimeException("Cannot drop tenant_id from [{$table}] because it is the only column.");
        }

        $replacementIndexes = $this->replacementIndexes($indexes, $table);
        $existingIndexSql = array_values(array_filter(
            array_map(
                fn (array $index): ?string => !$this->indexContainsTenantId($index) && is_string($index['sql'])
                    ? $index['sql']
                    : null,
                $indexes,
            ),
        ));

        $connection->beginTransaction();

        try {
            $connection->statement('DROP TABLE IF EXISTS ' . $this->sqliteQuoteIdentifier($temporary));
            $connection->statement($this->sqliteCreateTableWithoutTenantId($createSql, $table, $temporary));

            $columnList = $this->sqliteColumnList($columns);

            $connection->statement(sprintf(
                'INSERT INTO %s (%s) SELECT %s FROM %s',
                $this->sqliteQuoteIdentifier($temporary),
                $columnList,
                $columnList,
                $this->sqliteQuoteIdentifier($table),
            ));

            $connection->statement('DROP TABLE ' . $this->sqliteQuoteIdentifier($table));
            $connection->statement(sprintf(
                'ALTER TABLE %s RENAME TO %s',
                $this->sqliteQuoteIdentifier($temporary),
                $this->sqliteQuoteIdentifier($table),
            ));

            foreach ($existingIndexSql as $sql) {
                $connection->statement($sql);
            }

            foreach ($replacementIndexes as $index) {
                $connection->statement(sprintf(
                    'CREATE %sINDEX %s ON %s (%s)',
                    $index['unique'] ? 'UNIQUE ' : '',
                    $this->sqliteQuoteIdentifier($index['name']),
                    $this->sqliteQuoteIdentifier($table),
                    $this->sqliteColumnList($this->columnNames($index['columns'])),
                ));
            }

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }
    }

    /**
     * @return array<int, string>
     */
    private function sqliteColumns(Connection $connection, string $table): array
    {
        return array_map(
            fn (object $row): string => (string) $row->name,
            $connection->select('PRAGMA table_info(' . $this->sqliteQuoteIdentifier($table) . ')'),
        );
    }

    /**
     * @return array<int, array{name: string, unique: bool, columns: array<int, array{name: string, sub_part: int|null, descending: bool}>, origin?: string, sql?: string|null}>
     */
    private function sqliteIndexes(Connection $connection, string $table): array
    {
        $sqlByName = [];

        foreach ($connection->select(
            'SELECT name, sql FROM sqlite_master WHERE type = ? AND tbl_name = ?',
            ['index', $table],
        ) as $row) {
            $sqlByName[(string) $row->name] = $row->sql === null ? null : (string) $row->sql;
        }

        $indexes = [];

        foreach ($connection->select('PRAGMA index_list(' . $this->sqliteQuoteIdentifier($table) . ')') as $row) {
            $name = (string) $row->name;
            $columns = [];

            foreach ($connection->select('PRAGMA index_xinfo(' . $this->sqliteQuoteIdentifier($name) . ')') as $column) {
                if (property_exists($column, 'key') && (int) $column->key !== 1) {
                    continue;
                }

                if ($column->name === null) {
                    continue;
                }

                $columns[] = [
                    'name' => (string) $column->name,
                    'sub_part' => null,
                    'descending' => (bool) ($column->desc ?? false),
                ];
            }

            $indexes[] = [
                'name' => $name,
                'unique' => (int) $row->unique === 1,
                'columns' => $columns,
                'origin' => (string) ($row->origin ?? ''),
                'sql' => $sqlByName[$name] ?? null,
            ];
        }

        return $indexes;
    }

    private function sqliteCreateTableWithoutTenantId(string $createSql, string $table, string $temporary): string
    {
        $open = strpos($createSql, '(');
        $close = strrpos($createSql, ')');

        if ($open === false || $close === false || $close <= $open) {
            throw new RuntimeException("Cannot parse CREATE TABLE SQL for [{$table}].");
        }

        $definitions = [];

        foreach ($this->splitSqlList(substr($createSql, $open + 1, $close - $open - 1)) as $definition) {
            $definition = $this->sqliteDefinitionWithoutTenantId($definition, $table);

            if ($definition !== null) {
                $definitions[] = $definition;
            }
        }

        if ($definitions === []) {
            throw new RuntimeException("Cannot rebuild [{$table}] without tenant_id because no columns remain.");
        }

        $rebuilt = substr($createSql, 0, $open + 1)
            . implode(', ', $definitions)
            . substr($createSql, $close);

        if (preg_match('/\btenant_id\b/i', $rebuilt) === 1) {
            throw new RuntimeException("Cannot rebuild [{$table}] because tenant_id remains in the table definition.");
        }

        return preg_replace(
            '/\ACREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:"[^"]+"|`[^`]+`|\[[^\]]+\]|[^\s(]+)/i',
            'CREATE TABLE ' . $this->sqliteQuoteIdentifier($temporary),
            $rebuilt,
            1,
        ) ?? throw new RuntimeException("Cannot rewrite CREATE TABLE SQL for [{$table}].");
    }

    private function sqliteDefinitionWithoutTenantId(string $definition, string $table): ?string
    {
        if ($this->sqliteDefinitionStartsWithColumn($definition, 'tenant_id')) {
            return null;
        }

        if (preg_match('/\btenant_id\b/i', $definition) !== 1) {
            return $definition;
        }

        if (preg_match('/\bprimary\s+key\b/i', $definition) === 1) {
            throw new RuntimeException("Cannot drop tenant_id from [{$table}] because it participates in the primary key.");
        }

        if (preg_match('/\bforeign\s+key\b/i', $definition) === 1) {
            return null;
        }

        if (preg_match('/\bunique\s*\(/i', $definition) === 1) {
            return $this->sqliteUniqueDefinitionWithoutTenantId($definition);
        }

        return null;
    }

    private function sqliteUniqueDefinitionWithoutTenantId(string $definition): ?string
    {
        $uniquePosition = stripos($definition, 'unique');
        $open = $uniquePosition === false ? false : strpos($definition, '(', $uniquePosition);

        if ($open === false) {
            return null;
        }

        $close = $this->matchingParenthesisPosition($definition, $open);
        $columns = array_values(array_filter(
            $this->splitSqlList(substr($definition, $open + 1, $close - $open - 1)),
            fn (string $column): bool => !$this->sqliteListItemStartsWithColumn($column, 'tenant_id'),
        ));

        if ($columns === []) {
            return null;
        }

        return substr($definition, 0, $open + 1)
            . implode(', ', $columns)
            . substr($definition, $close);
    }

    private function matchingParenthesisPosition(string $sql, int $open): int
    {
        $depth = 0;
        $quote = null;
        $length = strlen($sql);

        for ($i = $open; $i < $length; $i++) {
            $char = $sql[$i];

            if ($quote !== null) {
                if ($char === $quote) {
                    if (($quote === '\'' || $quote === '"' || $quote === '`') && ($sql[$i + 1] ?? null) === $quote) {
                        $i++;
                        continue;
                    }

                    $quote = null;
                }

                continue;
            }

            if ($char === '\'' || $char === '"' || $char === '`') {
                $quote = $char;
                continue;
            }

            if ($char === '[') {
                $quote = ']';
                continue;
            }

            if ($char === '(') {
                $depth++;
                continue;
            }

            if ($char === ')') {
                $depth--;

                if ($depth === 0) {
                    return $i;
                }
            }
        }

        throw new RuntimeException('Cannot find matching parenthesis in SQLite table definition.');
    }

    /**
     * @return array<int, string>
     */
    private function splitSqlList(string $sql): array
    {
        $parts = [];
        $depth = 0;
        $quote = null;
        $start = 0;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];

            if ($quote !== null) {
                if ($char === $quote) {
                    if (($quote === '\'' || $quote === '"' || $quote === '`') && ($sql[$i + 1] ?? null) === $quote) {
                        $i++;
                        continue;
                    }

                    $quote = null;
                }

                continue;
            }

            if ($char === '\'' || $char === '"' || $char === '`') {
                $quote = $char;
                continue;
            }

            if ($char === '[') {
                $quote = ']';
                continue;
            }

            if ($char === '(') {
                $depth++;
                continue;
            }

            if ($char === ')') {
                $depth--;
                continue;
            }

            if ($char === ',' && $depth === 0) {
                $parts[] = trim(substr($sql, $start, $i - $start));
                $start = $i + 1;
            }
        }

        $parts[] = trim(substr($sql, $start));

        return array_values(array_filter($parts, fn (string $part): bool => $part !== ''));
    }

    private function sqliteDefinitionStartsWithColumn(string $definition, string $column): bool
    {
        return preg_match(
            '/^\s*(?:"' . preg_quote($column, '/') . '"|`' . preg_quote($column, '/') . '`|\[' . preg_quote($column, '/') . '\]|' . preg_quote($column, '/') . ')\b/i',
            $definition,
        ) === 1;
    }

    private function sqliteListItemStartsWithColumn(string $definition, string $column): bool
    {
        return preg_match(
            '/^\s*(?:"' . preg_quote($column, '/') . '"|`' . preg_quote($column, '/') . '`|\[' . preg_quote($column, '/') . '\]|' . preg_quote($column, '/') . ')(?:\s|$)/i',
            $definition,
        ) === 1;
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function sqliteColumnList(array $columns): string
    {
        return implode(', ', array_map(fn (string $column): string => $this->sqliteQuoteIdentifier($column), $columns));
    }

    private function sqliteQuoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    /**
     * @return array<int, string>
     */
    private function tablesWithTenantId(Connection $connection, string $database): array
    {
        return array_map(
            fn (object $row): string => (string) $row->TABLE_NAME,
            $connection->select(
                <<<'SQL'
                SELECT c.TABLE_NAME
                  FROM INFORMATION_SCHEMA.COLUMNS c
                  JOIN INFORMATION_SCHEMA.TABLES t
                    ON t.TABLE_SCHEMA = c.TABLE_SCHEMA
                   AND t.TABLE_NAME = c.TABLE_NAME
                 WHERE c.TABLE_SCHEMA = ?
                   AND c.COLUMN_NAME = 'tenant_id'
                   AND t.TABLE_TYPE = 'BASE TABLE'
                 ORDER BY c.TABLE_NAME
                SQL,
                [$database],
            ),
        );
    }

    private function dropTenantIdFromTable(Connection $connection, string $database, string $table): void
    {
        $indexes = $this->indexes($connection, $database, $table);
        $replacementIndexes = $this->replacementIndexes($indexes, $table);

        foreach ($indexes as $index) {
            if ($index['name'] === 'PRIMARY' && $this->indexContainsTenantId($index)) {
                throw new RuntimeException("Cannot drop tenant_id from [{$table}] because it participates in the primary key.");
            }
        }

        foreach ($this->foreignKeysUsingTenantId($connection, $database, $table) as $foreignKey) {
            $connection->statement(sprintf(
                'ALTER TABLE %s DROP FOREIGN KEY %s',
                $this->quoteIdentifier($table),
                $this->quoteIdentifier($foreignKey),
            ));
        }

        foreach ($indexes as $index) {
            if ($index['name'] === 'PRIMARY' || !$this->indexContainsTenantId($index)) {
                continue;
            }

            $connection->statement(sprintf(
                'ALTER TABLE %s DROP INDEX %s',
                $this->quoteIdentifier($table),
                $this->quoteIdentifier($index['name']),
            ));
        }

        $connection->statement(sprintf(
            'ALTER TABLE %s DROP COLUMN %s',
            $this->quoteIdentifier($table),
            $this->quoteIdentifier('tenant_id'),
        ));

        foreach ($replacementIndexes as $index) {
            $connection->statement(sprintf(
                'ALTER TABLE %s ADD %sINDEX %s (%s)',
                $this->quoteIdentifier($table),
                $index['unique'] ? 'UNIQUE ' : '',
                $this->quoteIdentifier($index['name']),
                $this->columnList($index['columns']),
            ));
        }
    }

    /**
     * @return array<int, string>
     */
    private function foreignKeysUsingTenantId(Connection $connection, string $database, string $table): array
    {
        return array_map(
            fn (object $row): string => (string) $row->CONSTRAINT_NAME,
            $connection->select(
                <<<'SQL'
                SELECT DISTINCT CONSTRAINT_NAME
                  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = ?
                   AND TABLE_NAME = ?
                   AND COLUMN_NAME = 'tenant_id'
                   AND REFERENCED_TABLE_NAME IS NOT NULL
                 ORDER BY CONSTRAINT_NAME
                SQL,
                [$database, $table],
            ),
        );
    }

    /**
     * @return array<int, array{name: string, unique: bool, columns: array<int, array{name: string, sub_part: int|null, descending: bool}>}>
     */
    private function indexes(Connection $connection, string $database, string $table): array
    {
        $indexes = [];

        foreach ($connection->select(
            <<<'SQL'
            SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, SUB_PART, COLLATION
              FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
             ORDER BY INDEX_NAME, SEQ_IN_INDEX
            SQL,
            [$database, $table],
        ) as $row) {
            $name = (string) $row->INDEX_NAME;

            $indexes[$name] ??= [
                'name' => $name,
                'unique' => (int) $row->NON_UNIQUE === 0,
                'columns' => [],
            ];

            $indexes[$name]['columns'][] = [
                'name' => (string) $row->COLUMN_NAME,
                'sub_part' => $row->SUB_PART === null ? null : (int) $row->SUB_PART,
                'descending' => ($row->COLLATION ?? null) === 'D',
            ];
        }

        return array_values($indexes);
    }

    /**
     * @param  array<int, array{name: string, unique: bool, columns: array<int, array{name: string, sub_part: int|null, descending: bool}>}>  $indexes
     * @return array<int, array{name: string, unique: bool, columns: array<int, array{name: string, sub_part: int|null, descending: bool}>}>
     */
    private function replacementIndexes(array $indexes, string $table): array
    {
        $existingIndexes = array_filter(
            $indexes,
            fn (array $index): bool => !$this->indexContainsTenantId($index),
        );
        $replacements = [];

        foreach ($indexes as $index) {
            if ($index['name'] === 'PRIMARY' || !$this->indexContainsTenantId($index)) {
                continue;
            }

            $columns = array_values(array_filter(
                $index['columns'],
                fn (array $column): bool => $column['name'] !== 'tenant_id',
            ));

            if ($columns === [] || $this->indexAlreadyCovered($existingIndexes, $index['unique'], $columns)) {
                continue;
            }

            $signature = $this->indexSignature($index['unique'], $columns);

            $replacements[$signature] ??= [
                'name' => $this->replacementIndexName($table, $index['unique'], $columns),
                'unique' => $index['unique'],
                'columns' => $columns,
            ];
        }

        return array_values($replacements);
    }

    private function indexContainsTenantId(array $index): bool
    {
        foreach ($index['columns'] as $column) {
            if ($column['name'] === 'tenant_id') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array{name: string, unique: bool, columns: array<int, array{name: string, sub_part: int|null, descending: bool}>}>  $indexes
     * @param  array<int, array{name: string, sub_part: int|null, descending: bool}>  $columns
     */
    private function indexAlreadyCovered(array $indexes, bool $unique, array $columns): bool
    {
        foreach ($indexes as $index) {
            if ($this->columnNames($index['columns']) !== $this->columnNames($columns)) {
                continue;
            }

            if (!$unique || $index['unique']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array{name: string, sub_part: int|null, descending: bool}>  $columns
     */
    private function replacementIndexName(string $table, bool $unique, array $columns): string
    {
        $base = strtolower(preg_replace(
            '/[^a-zA-Z0-9_]+/',
            '_',
            $table . '_' . implode('_', $this->columnNames($columns)) . '_' . ($unique ? 'unique' : 'index'),
        ));
        $hash = substr(md5($table . '|' . $this->indexSignature($unique, $columns)), 0, 8);

        return substr($base, 0, 55) . '_' . $hash;
    }

    /**
     * @param  array<int, array{name: string, sub_part: int|null, descending: bool}>  $columns
     */
    private function indexSignature(bool $unique, array $columns): string
    {
        return ($unique ? 'unique:' : 'index:') . implode(',', $this->columnNames($columns));
    }

    /**
     * @param  array<int, array{name: string, sub_part: int|null, descending: bool}>  $columns
     * @return array<int, string>
     */
    private function columnNames(array $columns): array
    {
        return array_map(fn (array $column): string => $column['name'], $columns);
    }

    /**
     * @param  array<int, array{name: string, sub_part: int|null, descending: bool}>  $columns
     */
    private function columnList(array $columns): string
    {
        return implode(', ', array_map(function (array $column): string {
            $sql = $this->quoteIdentifier($column['name']);

            if ($column['sub_part'] !== null) {
                $sql .= '(' . $column['sub_part'] . ')';
            }

            if ($column['descending']) {
                $sql .= ' DESC';
            }

            return $sql;
        }, $columns));
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
};
