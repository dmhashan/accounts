<?php

use App\Support\SidebarPermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $timestamp = now();

        foreach (SidebarPermissionCatalog::permissions() as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                [
                    'name' => $permission['name'],
                    'feature' => $permission['feature'],
                    'description' => $permission['description'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
            );
        }

        if (!Schema::hasTable('roles') || !Schema::hasTable('role_permission')) {
            return;
        }

        $adminRoleIds = DB::table('roles')
            ->whereIn('slug', SidebarPermissionCatalog::adminRoleSlugs())
            ->pluck('id');

        if ($adminRoleIds->isEmpty()) {
            return;
        }

        $campaignPermissionIds = DB::table('permissions')
            ->whereIn('slug', [
                'campaigns.view',
                'campaigns.create',
                'campaigns.edit',
                'campaigns.publish',
                'campaigns.close',
                'campaigns.delete',
                'campaigns.registrations',
                'campaigns.verify',
            ])
            ->pluck('id');

        $rows = [];

        foreach ($adminRoleIds as $roleId) {
            foreach ($campaignPermissionIds as $permissionId) {
                $rows[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('role_permission')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        // Keep campaign permission rows and grants on rollback to avoid surprising live role changes.
    }
};
