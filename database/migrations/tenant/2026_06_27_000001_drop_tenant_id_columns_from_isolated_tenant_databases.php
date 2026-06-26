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

        if (!in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $database = $connection->getDatabaseName();

        foreach ($this->tablesWithTenantId($connection, $database) as $table) {
            $this->dropTenantIdFromTable($connection, $database, $table);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Dropping tenant_id columns is not reversible because historical tenant ownership values are removed.');
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
