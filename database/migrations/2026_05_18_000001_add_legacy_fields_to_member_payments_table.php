<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_payments', function (Blueprint $table) {
            $table->string('legacy_uuid')->nullable()->after('notes')->index();
            $table->unsignedInteger('legacy_member_id')->nullable()->after('legacy_uuid')->index();
            $table->string('legacy_username')->nullable()->after('legacy_member_id');

            // One record per legacy payment UUID per tenant
            $table->unique(['tenant_id', 'legacy_uuid'], 'mp_tenant_legacy_uuid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('member_payments', function (Blueprint $table) {
            $table->dropUnique('mp_tenant_legacy_uuid_unique');
            $table->dropIndex(['legacy_member_id']);
            $table->dropIndex(['legacy_uuid']);
            $table->dropColumn(['legacy_uuid', 'legacy_member_id', 'legacy_username']);
        });
    }
};
