<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_accounts', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'account_type']);
            $table->dropColumn(['account_type', 'bank_name', 'account_number']);
        });
    }

    public function down(): void
    {
        Schema::table('company_accounts', function (Blueprint $table) {
            $table->string('account_type', 50)->default('bank')->after('name');
            $table->string('bank_name')->nullable()->after('account_type');
            $table->string('account_number')->nullable()->after('bank_name');
            $table->index(['tenant_id', 'account_type']);
        });
    }
};
