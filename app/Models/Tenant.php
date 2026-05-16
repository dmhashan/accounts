<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'domain',
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
        if (config('app.multitenancy_enabled', true)) {
            $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?? 'https';
            return "{$scheme}://{$this->domain}." . config('app.domain') . '/profile';
        }

        return rtrim(config('app.url'), '/') . '/profile';
    }
}
