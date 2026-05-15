<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['slug' => 'forms.manage'],
            ['name' => 'Manage Forms', 'feature' => 'Forms']
        );

        Role::whereIn('slug', ['super-admin', 'admin', 'owner'])
            ->get()
            ->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$permission->id]));
    }

    public function down(): void
    {
        Permission::where('slug', 'forms.manage')->delete();
    }
};
