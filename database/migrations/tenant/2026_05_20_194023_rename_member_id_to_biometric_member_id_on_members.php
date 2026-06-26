<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: drop the dedicated 4-digit biometric_member_id column and its composite index
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'biometric_member_id')) {
                $table->dropIndex('members_tenant_biometric_member_id_unique');
                $table->dropColumn('biometric_member_id');
            }
        });

        // Step 2: rename member_id → biometric_member_id (keeps existing unique index)
        Schema::table('members', function (Blueprint $table) {
            $table->renameColumn('member_id', 'biometric_member_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->renameColumn('biometric_member_id', 'member_id');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->string('biometric_member_id', 20)->nullable()->after('member_id');
            $table->unique(['tenant_id', 'biometric_member_id'], 'members_tenant_biometric_member_id_unique');
        });
    }
};
