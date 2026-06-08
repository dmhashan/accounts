<?php

namespace App\Models;

use App\Services\MemberPortalUrlService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'tenant_uuid',
        'use_custom_landing_page',
        'address',
        'email',
        'phone',
        'logo_path',
    ];

    protected $casts = [
        'use_custom_landing_page' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Returns the public member profile portal URL for this tenant.
     */
    public function profileUrl(): string
    {
        return app(MemberPortalUrlService::class)->urlForTenant($this);
    }
}
