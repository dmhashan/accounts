<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();

        $permissions = [
            [
                'name' => 'Create Sales',
                'slug' => 'sales.create',
                'feature' => 'Sales',
                'description' => 'Create new sales',
            ],
            [
                'name' => 'Edit Sales',
                'slug' => 'sales.edit',
                'feature' => 'Sales',
                'description' => 'Edit existing sales',
            ],
            [
                'name' => 'Delete Sales',
                'slug' => 'sales.delete',
                'feature' => 'Sales',
                'description' => 'Delete sales',
            ],
        ];

        foreach ($permissions as $permission) {
            $existing = DB::table('permissions')
                ->where('slug', $permission['slug'])
                ->first();

            if ($existing) {
                DB::table('permissions')
                    ->where('id', $existing->id)
                    ->update([
                        'name' => $permission['name'],
                        'feature' => $permission['feature'],
                        'description' => $permission['description'],
                        'updated_at' => $timestamp,
                    ]);

                continue;
            }

            DB::table('permissions')->insert([
                'name' => $permission['name'],
                'slug' => $permission['slug'],
                'feature' => $permission['feature'],
                'description' => $permission['description'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        $existingSalesRoleIds = DB::table('role_permission')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('permissions.slug', 'sales.process')
            ->pluck('role_permission.role_id')
            ->unique()
            ->values();

        $newPermissionIds = DB::table('permissions')
            ->whereIn('slug', ['sales.create', 'sales.edit', 'sales.delete'])
            ->pluck('id');

        $rows = [];

        foreach ($existingSalesRoleIds as $roleId) {
            foreach ($newPermissionIds as $permissionId) {
                $rows[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('role_permission')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('slug', ['sales.create', 'sales.edit', 'sales.delete'])
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('role_permission')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::table('permissions')
            ->whereIn('slug', ['sales.create', 'sales.edit', 'sales.delete'])
            ->delete();
    }
};
