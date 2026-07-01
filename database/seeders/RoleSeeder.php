<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\SidebarPermissionCatalog;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (SidebarPermissionCatalog::permissions() as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                [
                    'name' => $permission['name'],
                    'feature' => $permission['feature'],
                    'description' => $permission['description'],
                ],
            );
        }

        // Create Admin role (uneditable)
        $admin = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'description' => 'Administrator with full access to all features',
                'is_editable' => false,
            ],
        );

        // Create Member role (uneditable)
        $member = Role::firstOrCreate(
            ['slug' => 'member'],
            [
                'name' => 'Member',
                'description' => 'Regular member with limited access',
                'is_editable' => false,
            ],
        );

        // Admin always receives every configured permission.
        $adminPermissions = Permission::query()->pluck('id');
        $admin->permissions()->sync($adminPermissions);

        // Assign only member features to Member role
        $memberPermissions = Permission::whereIn('slug', SidebarPermissionCatalog::memberRolePermissionSlugs())->pluck('id');
        $member->permissions()->sync($memberPermissions);
    }
}
