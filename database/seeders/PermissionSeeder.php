<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // User Management
            ['name' => 'View Users', 'slug' => 'users.view', 'feature' => 'User Management', 'description' => 'View all users in the system'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'feature' => 'User Management', 'description' => 'Create new users'],
            ['name' => 'Edit Users', 'slug' => 'users.edit', 'feature' => 'User Management', 'description' => 'Edit existing users'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'feature' => 'User Management', 'description' => 'Delete users'],
            
            // Role Management
            ['name' => 'View Roles', 'slug' => 'roles.view', 'feature' => 'Role Management', 'description' => 'View all roles'],
            ['name' => 'Manage Permissions', 'slug' => 'roles.permissions', 'feature' => 'Role Management', 'description' => 'Assign permissions to roles'],
            
            // Reports
            ['name' => 'View Reports', 'slug' => 'reports.view', 'feature' => 'Reports', 'description' => 'View system reports'],
            ['name' => 'Export Reports', 'slug' => 'reports.export', 'feature' => 'Reports', 'description' => 'Export reports'],
            
            // Settings
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'feature' => 'Settings', 'description' => 'Manage system settings'],
            
            // Dashboard
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'feature' => 'Dashboard', 'description' => 'Access the dashboard'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}
