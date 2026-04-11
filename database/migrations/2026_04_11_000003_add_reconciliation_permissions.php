<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $manage = Permission::firstOrCreate(
            ['slug' => 'reconciliation.manage'],
            [
                'name' => 'Manage Reconciliation',
                'feature' => 'Reconciliation',
                'description' => 'Configure reconciliation forms and view all session history',
            ]
        );

        $perform = Permission::firstOrCreate(
            ['slug' => 'reconciliation.perform'],
            [
                'name' => 'Perform Reconciliation',
                'feature' => 'Reconciliation',
                'description' => 'Open and close daily reconciliation sessions',
            ]
        );

        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $admin->permissions()->syncWithoutDetaching([$manage->id, $perform->id]);
        }
    }

    public function down(): void
    {
        Permission::whereIn('slug', ['reconciliation.manage', 'reconciliation.perform'])->delete();
    }
};
