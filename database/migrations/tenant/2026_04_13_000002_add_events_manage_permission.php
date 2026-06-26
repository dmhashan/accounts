<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();

        $existing = DB::table('permissions')->where('slug', 'events.manage')->first();

        if ($existing) {
            DB::table('permissions')
                ->where('id', $existing->id)
                ->update([
                    'name' => 'Manage Events',
                    'feature' => 'Events',
                    'description' => 'Create, edit, and manage events and view registrations',
                    'updated_at' => $timestamp,
                ]);
            $permissionId = $existing->id;
        } else {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'Manage Events',
                'slug' => 'events.manage',
                'feature' => 'Events',
                'description' => 'Create, edit, and manage events and view registrations',
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

    public function down(): void
    {
        DB::table('permissions')->where('slug', 'events.manage')->delete();
    }
};
