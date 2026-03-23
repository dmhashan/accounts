<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionId = DB::table('permissions')
            ->where('slug', 'pos.sales')
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

    public function down(): void
    {
        $timestamp = now();

        $exists = DB::table('permissions')
            ->where('slug', 'pos.sales')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('permissions')->insert([
            'name' => 'Process Sales',
            'slug' => 'pos.sales',
            'feature' => 'POS',
            'description' => 'Access POS sales module',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
};
