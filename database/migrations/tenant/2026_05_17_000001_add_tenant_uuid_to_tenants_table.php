<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->uuid('tenant_uuid')->nullable()->after('id');
        });

        // Backfill existing records
        DB::table('tenants')->whereNull('tenant_uuid')->get()->each(function (object $tenant) {
            DB::table('tenants')
                ->where('id', $tenant->id)
                ->update(['tenant_uuid' => Str::uuid()->toString()]);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->uuid('tenant_uuid')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('tenant_uuid');
        });
    }
};
