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
                'name' => 'Manage Payments',
                'slug' => 'payments.manage',
                'feature' => 'Payments',
                'description' => 'Record and manage member daily payments',
            ],
        ];

        foreach ($permissions as $permission) {
            $existing = DB::table('permissions')->where('slug', $permission['slug'])->first();

            if ($existing) {
                DB::table('permissions')
                    ->where('id', $existing->id)
                    ->update([
                        'name' => $permission['name'],
                        'feature' => $permission['feature'],
                        'description' => $permission['description'],
                        'updated_at' => $timestamp,
                    ]);
                $permissionId = $existing->id;
            } else {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $permission['name'],
                    'slug' => $permission['slug'],
                    'feature' => $permission['feature'],
                    'description' => $permission['description'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }

            $adminRoleIds = DB::table('roles')->where('slug', 'admin')->pluck('id');

            $rows = [];
            foreach ($adminRoleIds as $roleId) {
                $rows[] = ['role_id' => $roleId, 'permission_id' => $permissionId];
            }

            if ($rows !== []) {
                DB::table('role_permission')->insertOrIgnore($rows);
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('slug', 'payments.manage')->delete();
    }
};
