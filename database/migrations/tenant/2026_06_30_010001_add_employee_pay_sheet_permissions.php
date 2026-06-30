<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            [
                'slug' => 'employees.manage',
                'name' => 'Manage Employees',
                'feature' => 'Employees',
                'description' => 'Create employees, manage documents, and record employee attendance.',
            ],
            [
                'slug' => 'employee_pay_sheets.manage',
                'name' => 'Manage Employee Pay Sheet',
                'feature' => 'Employee Pay Sheet',
                'description' => 'Generate and manage employee pay sheet runs.',
            ],
        ];

        $permissionIds = [];

        foreach ($permissions as $permission) {
            $record = Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                [
                    'name' => $permission['name'],
                    'feature' => $permission['feature'],
                    'description' => $permission['description'],
                ],
            );

            $permissionIds[] = $record->id;
        }

        Role::whereIn('slug', ['super-admin', 'admin', 'owner'])
            ->get()
            ->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching($permissionIds));
    }

    public function down(): void
    {
        Permission::whereIn('slug', ['employees.manage', 'employee_pay_sheets.manage'])->delete();
    }
};
