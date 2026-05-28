<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (!Schema::hasColumn('members', 'biometric_member_id')) {
                $table->string('biometric_member_id', 20)->nullable();
            }

            if (!Schema::hasIndex('members', 'members_tenant_biometric_member_id_unique')) {
                $table->unique(['tenant_id', 'biometric_member_id'], 'members_tenant_biometric_member_id_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique('members_tenant_biometric_member_id_unique');
            $table->dropColumn('biometric_member_id');
        });
    }
};
