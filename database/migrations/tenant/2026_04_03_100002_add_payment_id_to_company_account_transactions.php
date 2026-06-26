<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // If the refactor migration already ran, payment_id is no longer part of the schema.
        if (Schema::hasColumn('company_account_transactions', 'model_name')) {
            return;
        }

        Schema::table('company_account_transactions', function (Blueprint $table) {
            $table->foreignId('payment_id')
                ->nullable()
                ->after('expense_id')
                ->constrained('member_payments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('company_account_transactions', 'payment_id')) {
            return;
        }

        Schema::table('company_account_transactions', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->dropColumn('payment_id');
        });
    }
};
