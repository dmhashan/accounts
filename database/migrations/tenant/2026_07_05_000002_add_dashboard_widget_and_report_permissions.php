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

        $this->copyExistingGrants($timestamp);
        $this->removeLegacyReportViewPermission();
        $this->grantAdminRolesAllPermissions($timestamp);
    }

    public function down(): void
    {
        // Keep permission rows and grants on rollback to avoid surprising live role changes.
    }

    private function copyExistingGrants(mixed $timestamp): void
    {
        $grantMap = [
            'dashboard.view' => [
                'dashboard.widget.cash_flow',
                'dashboard.widget.auth_details',
                'dashboard.widget.stock_availability',
            ],
            'accounts.manage' => ['dashboard.widget.cash_flow'],
            'accounts.transactions' => ['dashboard.widget.cash_flow'],
            'inventory.manage' => ['dashboard.widget.stock_availability'],
            'inventory.stock' => ['dashboard.widget.stock_availability'],
            'reports.view' => [
                'reports.daily_summary',
                'reports.real_profit',
                'reports.statistics',
                'reports.member_analysis',
                'reports.customers',
                'reports.products',
            ],
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', array_values(array_unique(array_merge(
                array_keys($grantMap),
                ...array_values($grantMap),
            ))))
            ->pluck('id', 'slug');

        $rows = [];

        foreach ($grantMap as $sourceSlug => $targetSlugs) {
            $sourceId = $permissionIds[$sourceSlug] ?? null;

            if (!$sourceId) {
                continue;
            }

            $roleIds = DB::table('role_permission')
                ->where('permission_id', $sourceId)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                foreach ($targetSlugs as $targetSlug) {
                    $targetId = $permissionIds[$targetSlug] ?? null;

                    if (!$targetId) {
                        continue;
                    }

                    $rows[] = [
                        'role_id' => $roleId,
                        'permission_id' => $targetId,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
            }
        }

        if ($rows !== []) {
            DB::table('role_permission')->insertOrIgnore($rows);
        }
    }

    private function removeLegacyReportViewPermission(): void
    {
        $permissionId = DB::table('permissions')
            ->where('slug', 'reports.view')
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

    private function grantAdminRolesAllPermissions(mixed $timestamp): void
    {
        $adminRoleIds = DB::table('roles')
            ->whereIn('slug', SidebarPermissionCatalog::adminRoleSlugs())
            ->pluck('id');

        if ($adminRoleIds->isEmpty()) {
            return;
        }

        $permissionIds = DB::table('permissions')->pluck('id');
        $rows = [];

        foreach ($adminRoleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
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
};
