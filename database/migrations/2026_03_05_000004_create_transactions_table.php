<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transactions')) {
            $this->createTransactionsTable();

            return;
        }

        $this->syncTransactionsTable();
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }

    private function createTransactionsTable(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->enum('transaction_reference_type', ['company_account', 'wallet', 'sale']);
            $table->unsignedBigInteger('reference_id');
            $table->decimal('amount', 14, 2);
            $table->enum('transaction_type', ['credit', 'debit']);
            $table->decimal('balance_before', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('completed');
            $table->timestamp('transaction_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'transaction_reference_type', 'reference_id'], 'transactions_reference_lookup_idx');
            $table->index(['tenant_id', 'transaction_date'], 'transactions_tenant_date_idx');
            $table->index(['tenant_id', 'status'], 'transactions_tenant_status_idx');
        });
    }

    private function syncTransactionsTable(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable();
            }

            if (!Schema::hasColumn('transactions', 'transaction_reference_type')) {
                $table->enum('transaction_reference_type', ['company_account', 'wallet', 'sale'])->nullable();
            }

            if (!Schema::hasColumn('transactions', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable();
            }

            if (!Schema::hasColumn('transactions', 'amount')) {
                $table->decimal('amount', 14, 2)->default(0);
            }

            if (!Schema::hasColumn('transactions', 'transaction_type')) {
                $table->enum('transaction_type', ['credit', 'debit'])->nullable();
            }

            if (!Schema::hasColumn('transactions', 'balance_before')) {
                $table->decimal('balance_before', 14, 2)->default(0);
            }

            if (!Schema::hasColumn('transactions', 'balance_after')) {
                $table->decimal('balance_after', 14, 2)->default(0);
            }

            if (!Schema::hasColumn('transactions', 'description')) {
                $table->text('description')->nullable();
            }

            if (!Schema::hasColumn('transactions', 'status')) {
                $table->enum('status', ['pending', 'completed', 'cancelled'])->default('completed');
            }

            if (!Schema::hasColumn('transactions', 'transaction_date')) {
                $table->timestamp('transaction_date')->nullable();
            }

            if (!Schema::hasColumn('transactions', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }

            if (!Schema::hasColumn('transactions', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable();
            }

            if (!Schema::hasColumn('transactions', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (!Schema::hasColumn('transactions', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        if (
            Schema::hasColumn('transactions', 'tenant_id')
            && !$this->foreignKeyExists('transactions', 'transactions_tenant_id_foreign')
            && !$this->hasOrphanReferences('transactions', 'tenant_id', 'tenants', 'id')
        ) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            });
        }

        if (
            Schema::hasColumn('transactions', 'created_by')
            && Schema::hasTable('users')
            && !$this->foreignKeyExists('transactions', 'transactions_created_by_foreign')
            && !$this->hasOrphanReferences('transactions', 'created_by', 'users', 'id')
        ) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (
            Schema::hasColumn('transactions', 'updated_by')
            && Schema::hasTable('users')
            && !$this->foreignKeyExists('transactions', 'transactions_updated_by_foreign')
            && !$this->hasOrphanReferences('transactions', 'updated_by', 'users', 'id')
        ) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (
            Schema::hasColumn('transactions', 'tenant_id')
            && Schema::hasColumn('transactions', 'transaction_reference_type')
            && Schema::hasColumn('transactions', 'reference_id')
            && !$this->indexExists('transactions', 'transactions_reference_lookup_idx')
        ) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['tenant_id', 'transaction_reference_type', 'reference_id'], 'transactions_reference_lookup_idx');
            });
        }

        if (
            Schema::hasColumn('transactions', 'tenant_id')
            && Schema::hasColumn('transactions', 'transaction_date')
            && !$this->indexExists('transactions', 'transactions_tenant_date_idx')
        ) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['tenant_id', 'transaction_date'], 'transactions_tenant_date_idx');
            });
        }

        if (
            Schema::hasColumn('transactions', 'tenant_id')
            && Schema::hasColumn('transactions', 'status')
            && !$this->indexExists('transactions', 'transactions_tenant_status_idx')
        ) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['tenant_id', 'status'], 'transactions_tenant_status_idx');
            });
        }
    }

    private function hasOrphanReferences(string $table, string $column, string $referencedTable, string $referencedColumn): bool
    {
        if (!Schema::hasTable($referencedTable)) {
            return true;
        }

        return DB::table($table.' as source')
            ->leftJoin($referencedTable.' as target', 'target.'.$referencedColumn, '=', 'source.'.$column)
            ->whereNotNull('source.'.$column)
            ->whereNull('target.'.$referencedColumn)
            ->exists();
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select('SHOW INDEX FROM `'.$table.'`');

        foreach ($indexes as $index) {
            if (($index->Key_name ?? null) === $indexName) {
                return true;
            }
        }

        return false;
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
