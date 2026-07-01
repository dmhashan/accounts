<?php

namespace App\Models;

use App\Support\SidebarPermissionCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_editable',
    ];

    protected $casts = [
        'is_editable' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if (in_array($this->slug, SidebarPermissionCatalog::adminRoleSlugs(), true)) {
            return true;
        }

        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }

    public function givePermissionTo(Permission $permission): void
    {
        $this->permissions()->syncWithoutDetaching([$permission->id]);
    }

    public function removePermissionFrom(Permission $permission): void
    {
        $this->permissions()->detach($permission->id);
    }
}
