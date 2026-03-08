<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('company_accounts')) {
            $this->createCompanyAccountsTable();

            return;
        }

        $this->syncCompanyAccountsTable();
    }

    public function down(): void
    {
        Schema::dropIfExists('company_accounts');
    }

    private function createCompanyAccountsTable(): void
    {
        Schema::create('company_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('account_name');
            $table->text('description')->nullable();
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'account_name']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    private function syncCompanyAccountsTable(): void
    {
        $hadCurrentBalance = Schema::hasColumn('company_accounts', 'current_balance');
        $hadLegacyBalance = Schema::hasColumn('company_accounts', 'balance');

        Schema::table('company_accounts', function (Blueprint $table) use ($hadCurrentBalance) {
            if (!Schema::hasColumn('company_accounts', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable();
            }

            if (!Schema::hasColumn('company_accounts', 'account_name')) {
                $table->string('account_name')->nullable();
            }

            if (!Schema::hasColumn('company_accounts', 'description')) {
                $table->text('description')->nullable();
            }

            if (!$hadCurrentBalance) {
                $table->decimal('current_balance', 14, 2)->default(0);
            }

            if (!Schema::hasColumn('company_accounts', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }

            if (!Schema::hasColumn('company_accounts', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable();
            }

            if (!Schema::hasColumn('company_accounts', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (!Schema::hasColumn('company_accounts', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        if (!$hadCurrentBalance && $hadLegacyBalance) {
            DB::statement('UPDATE `company_accounts` SET `current_balance` = `balance`');
        }

        if (
            Schema::hasColumn('company_accounts', 'tenant_id')
            && !$this->foreignKeyExists('company_accounts', 'company_accounts_tenant_id_foreign')
            && !$this->hasOrphanTenantIds()
        ) {
            Schema::table('company_accounts', function (Blueprint $table) {
                $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            });
        }

        if (
            Schema::hasColumn('company_accounts', 'created_by')
            && Schema::hasTable('users')
            && !$this->foreignKeyExists('company_accounts', 'company_accounts_created_by_foreign')
            && !$this->hasOrphanUserReferences('created_by')
        ) {
            Schema::table('company_accounts', function (Blueprint $table) {
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (
            Schema::hasColumn('company_accounts', 'updated_by')
            && Schema::hasTable('users')
            && !$this->foreignKeyExists('company_accounts', 'company_accounts_updated_by_foreign')
            && !$this->hasOrphanUserReferences('updated_by')
        ) {
            Schema::table('company_accounts', function (Blueprint $table) {
                $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (
            Schema::hasColumn('company_accounts', 'tenant_id')
            && Schema::hasColumn('company_accounts', 'account_name')
            && !$this->indexExists('company_accounts', 'company_accounts_tenant_id_account_name_unique')
            && !$this->hasDuplicateTenantAccountNames()
        ) {
            Schema::table('company_accounts', function (Blueprint $table) {
                $table->unique(['tenant_id', 'account_name']);
            });
        }

        if (
            Schema::hasColumn('company_accounts', 'tenant_id')
            && Schema::hasColumn('company_accounts', 'created_at')
            && !$this->indexExists('company_accounts', 'company_accounts_tenant_id_created_at_index')
        ) {
            Schema::table('company_accounts', function (Blueprint $table) {
                $table->index(['tenant_id', 'created_at']);
            });
        }
    }

    private function hasDuplicateTenantAccountNames(): bool
    {
        return DB::table('company_accounts')
            ->select('tenant_id', 'account_name', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('tenant_id', 'account_name')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();
    }

    private function hasOrphanTenantIds(): bool
    {
        if (!Schema::hasTable('tenants')) {
            return true;
        }

        return DB::table('company_accounts as account')
            ->leftJoin('tenants as tenant', 'tenant.id', '=', 'account.tenant_id')
            ->whereNotNull('account.tenant_id')
            ->whereNull('tenant.id')
            ->exists();
    }

    private function hasOrphanUserReferences(string $column): bool
    {
        return DB::table('company_accounts as account')
            ->leftJoin('users as user', 'user.id', '=', 'account.'.$column)
            ->whereNotNull('account.'.$column)
            ->whereNull('user.id')
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
