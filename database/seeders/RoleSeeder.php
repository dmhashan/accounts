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

        // Assign all permissions to Admin
        $allPermissions = Permission::all();
        $admin->permissions()->sync($allPermissions->pluck('id'));

        // Member role has no special permissions - they can only access their own profile
        // and member-specific features (workout, diet, payments, attendance)
        $member->permissions()->sync([]);
    }
}
