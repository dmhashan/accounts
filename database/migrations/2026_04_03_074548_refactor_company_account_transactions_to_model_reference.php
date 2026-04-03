<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add new columns (guard against partial previous run)
        if (!Schema::hasColumn('company_account_transactions', 'model_name')) {
            Schema::table('company_account_transactions', function (Blueprint $table) {
                $table->string('model_name')->nullable()->after('company_account_id');
                $table->unsignedBigInteger('reference_id')->nullable()->after('model_name');
            });
        }

        // 2. Migrate existing data — guard each column individually
        if (Schema::hasColumn('company_account_transactions', 'sale_id')) {
            DB::statement("UPDATE company_account_transactions SET model_name = 'sale', reference_id = sale_id WHERE sale_id IS NOT NULL AND model_name IS NULL");
        }
        if (Schema::hasColumn('company_account_transactions', 'expense_id')) {
            DB::statement("UPDATE company_account_transactions SET model_name = 'expense', reference_id = expense_id WHERE expense_id IS NOT NULL AND model_name IS NULL");
        }
        if (Schema::hasColumn('company_account_transactions', 'payment_id')) {
            DB::statement("UPDATE company_account_transactions SET model_name = 'payment', reference_id = payment_id WHERE payment_id IS NOT NULL AND model_name IS NULL");
        }

        // 3. Drop old foreign keys, unique constraint, and columns — each guarded individually
        if (Schema::hasColumn('company_account_transactions', 'sale_id')) {
            Schema::table('company_account_transactions', function (Blueprint $table) {
                $table->dropForeign(['sale_id']);
                try { $table->dropUnique('cat_sale_type_unique'); } catch (\Throwable $e) {}
                $table->dropColumn('sale_id');
            });
        }
        if (Schema::hasColumn('company_account_transactions', 'expense_id')) {
            Schema::table('company_account_transactions', function (Blueprint $table) {
                $table->dropForeign(['expense_id']);
                $table->dropColumn('expense_id');
            });
        }
        if (Schema::hasColumn('company_account_transactions', 'payment_id')) {
            Schema::table('company_account_transactions', function (Blueprint $table) {
                $table->dropForeign(['payment_id']);
                $table->dropColumn('payment_id');
            });
        }

        // 4. Add a unique index (MySQL treats NULLs as distinct so manual entries won't conflict)
        $hasIndex = collect(Schema::getIndexes('company_account_transactions'))
            ->pluck('name')
            ->contains('cat_model_reference_unique');

        if (!$hasIndex) {
            Schema::table('company_account_transactions', function (Blueprint $table) {
                $table->unique(['model_name', 'reference_id'], 'cat_model_reference_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('company_account_transactions', function (Blueprint $table) {
            $table->dropUnique('cat_model_reference_unique');
            $table->dropColumn(['model_name', 'reference_id']);
        });

        Schema::table('company_account_transactions', function (Blueprint $table) {
            $table->foreignId('sale_id')->nullable()->after('company_account_id')->constrained('sales')->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->after('sale_id')->constrained('expenses')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->after('expense_id')->constrained('member_payments')->nullOnDelete();
            $table->unique(['sale_id', 'type'], 'cat_sale_type_unique');
        });
    }
};
