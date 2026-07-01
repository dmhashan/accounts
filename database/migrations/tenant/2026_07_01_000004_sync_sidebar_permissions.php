<?php

use App\Support\SidebarPermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles') || !Schema::hasTable('role_permission')) {
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
                    'updated_at' => $timestamp,
                    'created_at' => $timestamp,
                ],
            );
        }

        $this->copyExistingGrantsToSidebarChildren($timestamp);
        $this->removePermissionsOutsideSidebarCatalog();
        $this->grantAdminRolesAllPermissions($timestamp);
    }

    public function down(): void
    {
        // This migration intentionally keeps permission rows in place on rollback.
        // Removing live permission rows would detach role access unexpectedly.
    }

    private function copyExistingGrantsToSidebarChildren(mixed $timestamp): void
    {
        $grantMap = [
            'users.view' => ['members.view', 'members.temp.view'],
            'users.create' => ['members.create'],
            'users.edit' => ['members.edit'],
            'users.delete' => ['members.delete'],
            'inventory.manage' => ['inventory.audit'],
            'accounts.manage' => ['accounts.transfers', 'accounts.transactions', 'expenses.manage'],
            'sales.process' => ['sales.paid.view'],
            'payments.manage' => ['payment_plans.manage'],
            'reports.view' => ['reports.daily_summary', 'reports.real_profit', 'reports.customers', 'reports.products'],
            'settings.manage' => ['settings.configuration', 'settings.biometric', 'settings.legacy_tools'],
            'workouts.manage' => ['workouts.exercises', 'workouts.assignments'],
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

    private function removePermissionsOutsideSidebarCatalog(): void
    {
        $catalogSlugs = SidebarPermissionCatalog::slugs();

        $stalePermissionIds = DB::table('permissions')
            ->whereNotIn('slug', $catalogSlugs)
            ->pluck('id');

        if ($stalePermissionIds->isEmpty()) {
            return;
        }

        DB::table('role_permission')
            ->whereIn('permission_id', $stalePermissionIds)
            ->delete();

        DB::table('permissions')
            ->whereIn('id', $stalePermissionIds)
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
