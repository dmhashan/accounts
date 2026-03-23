<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();

        $permissionId = DB::table('permissions')
            ->where('slug', 'accounts.manage')
            ->value('id');

        if ($permissionId) {
            DB::table('permissions')
                ->where('id', $permissionId)
                ->update([
                    'name' => 'Manage Accounts',
                    'feature' => 'Accounting',
                    'description' => 'Access company accounts and transfers',
                    'updated_at' => $timestamp,
                ]);
        } else {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'Manage Accounts',
                'slug' => 'accounts.manage',
                'feature' => 'Accounting',
                'description' => 'Access company accounts and transfers',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        $adminRoleIds = DB::table('roles')
            ->where('slug', 'admin')
            ->pluck('id');

        $rows = [];

        foreach ($adminRoleIds as $roleId) {
            $rows[] = [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ];
        }

        if ($rows !== []) {
            DB::table('role_permission')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('slug', 'accounts.manage')
            ->value('id');

        if (!$permissionId) {
            return;
        }

        DB::table('role_permission')
            ->where('permission_id', $permissionId)
            ->delete();

        DB::table('permissions')
            ->where('id', $permissionId)
            ->delete();
    }
};
