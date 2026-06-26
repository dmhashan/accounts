<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $stockPermission = Permission::firstOrCreate(
            ['slug' => 'inventory.stock'],
            [
                'name' => 'Manage Stock',
                'feature' => 'Inventory',
                'description' => 'Create and manage stock entries',
            ],
        );

        $displayPermission = Permission::firstOrCreate(
            ['slug' => 'inventory.display'],
            [
                'name' => 'Manage Display',
                'feature' => 'Inventory',
                'description' => 'Release stock items to the display shelf for sale',
            ],
        );

        $admin = Role::where('slug', 'admin')->first();

        if ($admin) {
            $admin->permissions()->syncWithoutDetaching([$stockPermission->id, $displayPermission->id]);
        }
    }

    public function down(): void
    {
        Permission::whereIn('slug', ['inventory.stock', 'inventory.display'])->delete();
    }
};
