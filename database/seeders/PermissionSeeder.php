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
            // Admin Features
            ['name' => 'View Users', 'slug' => 'users.view', 'feature' => 'Admin Features', 'description' => 'View all users in the system'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'feature' => 'Admin Features', 'description' => 'Create new users'],
            ['name' => 'Edit Users', 'slug' => 'users.edit', 'feature' => 'Admin Features', 'description' => 'Edit existing users'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'feature' => 'Admin Features', 'description' => 'Delete users'],
            
            // Admin Features - Role Management
            ['name' => 'View Roles', 'slug' => 'roles.view', 'feature' => 'Admin Features', 'description' => 'View all roles'],
            ['name' => 'Manage Permissions', 'slug' => 'roles.permissions', 'feature' => 'Admin Features', 'description' => 'Assign permissions to roles'],
            
            // Admin Features - Reports
            ['name' => 'View Reports', 'slug' => 'reports.view', 'feature' => 'Admin Features', 'description' => 'View system reports'],
            ['name' => 'Export Reports', 'slug' => 'reports.export', 'feature' => 'Admin Features', 'description' => 'Export reports'],
            
            // Admin Features - Settings
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'feature' => 'Admin Features', 'description' => 'Manage system settings'],
            
            // Admin Features - Dashboard
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'feature' => 'Admin Features', 'description' => 'Access the admin dashboard'],
      
            // Member Features
            ['name' => 'View Profile', 'slug' => 'member.profile.view', 'feature' => 'Member Features', 'description' => 'View member profile'],
            ['name' => 'View Workout Schedule', 'slug' => 'member.workout.view', 'feature' => 'Member Features', 'description' => 'View workout schedule'],
            ['name' => 'View Diet Plan', 'slug' => 'member.diet.view', 'feature' => 'Member Features', 'description' => 'View diet plan'],
            ['name' => 'View Payments', 'slug' => 'member.payments.view', 'feature' => 'Member Features', 'description' => 'View payments history'],
            ['name' => 'View Attendance', 'slug' => 'member.attendance.view', 'feature' => 'Member Features', 'description' => 'View attendance records'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}
