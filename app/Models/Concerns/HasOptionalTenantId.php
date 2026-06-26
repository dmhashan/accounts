<?php

namespace App\Models\Concerns;

use App\Services\Tenancy\TenantColumn;
use Illuminate\Database\Eloquent\Builder;

trait HasOptionalTenantId
{
    public function scopeForTenant(Builder $query, int $tenantId, string $column = 'tenant_id'): Builder
    {
        /** @var Builder $query */
        return TenantColumn::scope($query, $tenantId, $column);
    }

    public function getTenantIdAttribute(mixed $value): ?int
    {
        return TenantColumn::modelTenantId($this) ?? ($value === null ? null : (int) $value);
    }

    public function setTenantIdAttribute(mixed $value): void
    {
        if (TenantColumn::has($this)) {
            $this->attributes['tenant_id'] = $value;
        }
    }

    public function belongsToTenant(int $tenantId): bool
    {
        return TenantColumn::matches($this, $tenantId);
    }
}
