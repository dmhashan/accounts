<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin role (uneditable)
        $admin = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'description' => 'Administrator with full access to all features',
                'is_editable' => false,
            ]
        );

        // Create Member role (uneditable)
        $member = Role::firstOrCreate(
            ['slug' => 'member'],
            [
                'name' => 'Member',
                'description' => 'Regular member with limited access',
                'is_editable' => false,
            ]
        );

        // Assign only admin features to Admin role
        $adminPermissions = Permission::where('feature', 'Admin Features')->pluck('id');
        $admin->permissions()->sync($adminPermissions);

        // Assign only member features to Member role
        $memberPermissions = Permission::where('feature', 'Member Features')->pluck('id');
        $member->permissions()->sync($memberPermissions);
    }
}
