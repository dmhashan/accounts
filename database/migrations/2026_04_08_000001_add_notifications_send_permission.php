<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();

        $existing = DB::table('permissions')->where('slug', 'notifications.send')->first();

        if ($existing) {
            DB::table('permissions')
                ->where('id', $existing->id)
                ->update([
                    'name'        => 'Send Notifications',
                    'feature'     => 'Notifications',
                    'description' => 'Send bulk SMS notifications to members',
                    'updated_at'  => $timestamp,
                ]);
            $permissionId = $existing->id;
        } else {
            $permissionId = DB::table('permissions')->insertGetId([
                'name'        => 'Send Notifications',
                'slug'        => 'notifications.send',
                'feature'     => 'Notifications',
                'description' => 'Send bulk SMS notifications to members',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
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

    public function down(): void
    {
        DB::table('permissions')->where('slug', 'notifications.send')->delete();
    }
};
