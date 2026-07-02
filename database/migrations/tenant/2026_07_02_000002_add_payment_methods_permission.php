<?php

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

        DB::table('permissions')->updateOrInsert(
            ['slug' => 'payment_methods.manage'],
            [
                'name' => 'Payment Methods',
                'feature' => 'Settings',
                'description' => 'View and manage payment methods and settlement rules from Settings.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        );

        $sourceId = DB::table('permissions')->where('slug', 'payments.manage')->value('id');
        $targetId = DB::table('permissions')->where('slug', 'payment_methods.manage')->value('id');

        if ($sourceId && $targetId) {
            $rows = DB::table('role_permission')
                ->where('permission_id', $sourceId)
                ->pluck('role_id')
                ->map(fn ($roleId) => [
                    'role_id' => $roleId,
                    'permission_id' => $targetId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])
                ->all();

            if ($rows !== []) {
                DB::table('role_permission')->insertOrIgnore($rows);
            }
        }
    }

    public function down(): void
    {
        // Keep permission rows/grants intact on rollback.
    }
};
