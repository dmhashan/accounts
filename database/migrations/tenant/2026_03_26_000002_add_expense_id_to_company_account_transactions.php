<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_account_transactions', function (Blueprint $table) {
            $table->foreignId('expense_id')
                ->nullable()
                ->after('sale_id')
                ->constrained('expenses')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('company_account_transactions', function (Blueprint $table) {
            $table->dropForeign(['expense_id']);
            $table->dropColumn('expense_id');
        });
    }
};
