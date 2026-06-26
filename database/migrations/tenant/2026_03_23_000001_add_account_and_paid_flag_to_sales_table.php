<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('account_id')
                ->nullable()
                ->after('customer_member_id')
                ->constrained('company_accounts')
                ->nullOnDelete();
            $table->boolean('is_paid')->default(false)->after('balance');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
            $table->dropColumn('is_paid');
        });
    }
};
