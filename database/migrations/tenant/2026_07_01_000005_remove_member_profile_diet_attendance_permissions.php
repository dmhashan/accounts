<?php

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

        DB::table('permissions')
            ->whereIn('slug', [
                'member.workout.view',
                'member.payments.view',
            ])
            ->update([
                'feature' => 'Member Portal',
                'updated_at' => $timestamp,
            ]);

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', [
                'member.profile.view',
                'member.diet.view',
                'member.attendance.view',
            ])
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        if (Schema::hasTable('role_permission')) {
            DB::table('role_permission')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::table('permissions')
            ->whereIn('id', $permissionIds)
            ->delete();
    }

    public function down(): void
    {
        // Removed sidebar permissions are intentionally not restored on rollback.
    }
};
