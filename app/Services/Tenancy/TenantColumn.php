<?php

namespace App\Services\Tenancy;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class TenantColumn
{
    /** @var array<string, bool> */
    private static array $cache = [];

    public static function has(object|string $subject): bool
    {
        [$connection, $table] = self::connectionAndTable($subject);
        $database = (string) ($connection->getDatabaseName() ?? '');
        $cacheKey = $connection->getName() . ':' . $database . ':' . $table;

        return self::$cache[$cacheKey]
            ??= $connection->getSchemaBuilder()->hasColumn($table, 'tenant_id');
    }

    public static function scope(EloquentBuilder|QueryBuilder $query, int $tenantId, string $column = 'tenant_id'): EloquentBuilder|QueryBuilder
    {
        if (self::has($query)) {
            $query->where($column, $tenantId);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function attributes(object|string $subject, int $tenantId, array $attributes = []): array
    {
        if (self::has($subject)) {
            $attributes['tenant_id'] = $tenantId;
        } else {
            unset($attributes['tenant_id']);
        }

        return $attributes;
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<int, string>
     */
    public static function select(object|string $subject, array $columns): array
    {
        if (self::has($subject)) {
            return $columns;
        }

        return array_values(array_filter(
            $columns,
            fn (string $column): bool => !self::isTenantIdColumn($column),
        ));
    }

    public static function unique(object|string $subject, int $tenantId, ?string $column = null): Unique
    {
        [, $table] = self::connectionAndTable($subject);
        $rule = Rule::unique($table, $column);

        if (self::has($subject)) {
            $rule->where(fn ($query) => $query->where('tenant_id', $tenantId));
        }

        return $rule;
    }

    public static function matches(Model $model, int $tenantId): bool
    {
        if (!self::has($model)) {
            return true;
        }

        return (int) $model->getAttribute('tenant_id') === $tenantId;
    }

    public static function modelTenantId(Model $model): ?int
    {
        if (self::has($model)) {
            $tenantId = $model->getAttributes()['tenant_id'] ?? null;

            return $tenantId === null ? null : (int) $tenantId;
        }

        if (app()->bound('tenant')) {
            return (int) app('tenant')->id;
        }

        return null;
    }

    /**
     * @return array{0: \Illuminate\Database\ConnectionInterface, 1: string}
     */
    private static function connectionAndTable(object|string $subject): array
    {
        if ($subject instanceof EloquentBuilder) {
            $subject = $subject->getModel();
        }

        if ($subject instanceof Model) {
            return [$subject->getConnection(), $subject->getTable()];
        }

        if ($subject instanceof QueryBuilder) {
            return [$subject->getConnection(), self::baseTableName((string) $subject->from)];
        }

        if (is_string($subject) && class_exists($subject) && is_subclass_of($subject, Model::class)) {
            $model = new $subject;

            return [$model->getConnection(), $model->getTable()];
        }

        return [DB::connection(), self::baseTableName((string) $subject)];
    }

    private static function baseTableName(string $table): string
    {
        $table = trim($table, '`" ');

        if (str_contains(strtolower($table), ' as ')) {
            $table = preg_split('/\s+as\s+/i', $table)[0] ?? $table;
        } elseif (str_contains($table, ' ')) {
            $table = explode(' ', $table)[0];
        }

        if (str_contains($table, '.')) {
            $table = substr($table, strrpos($table, '.') + 1);
        }

        return trim($table, '`" ');
    }

    private static function isTenantIdColumn(string $column): bool
    {
        $column = trim($column, '`" ');

        return $column === 'tenant_id' || str_ends_with($column, '.tenant_id');
    }
}
