<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenants') || Schema::hasColumn('tenants', 'wallet_credit_limit')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->decimal('wallet_credit_limit', 14, 2)->default(0)->after('use_custom_landing_page');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tenants') || !Schema::hasColumn('tenants', 'wallet_credit_limit')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('wallet_credit_limit');
        });
    }
};
