<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wallets')) {
            $this->createWalletsTable();

            return;
        }

        $this->syncWalletsTable();
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }

    private function createWalletsTable(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('member_id');
            $table->index(['tenant_id', 'status']);
        });
    }

    private function syncWalletsTable(): void
    {
        $hadCurrentBalance = Schema::hasColumn('wallets', 'current_balance');
        $hadLegacyBalance = Schema::hasColumn('wallets', 'balance');

        Schema::table('wallets', function (Blueprint $table) use ($hadCurrentBalance) {
            if (!Schema::hasColumn('wallets', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable();
            }

            if (!Schema::hasColumn('wallets', 'member_id')) {
                $table->unsignedBigInteger('member_id')->nullable();
            }

            if (!$hadCurrentBalance) {
                $table->decimal('current_balance', 14, 2)->default(0);
            }

            if (!Schema::hasColumn('wallets', 'status')) {
                $table->enum('status', ['active', 'suspended'])->default('active');
            }

            if (!Schema::hasColumn('wallets', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }

            if (!Schema::hasColumn('wallets', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable();
            }

            if (!Schema::hasColumn('wallets', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (!Schema::hasColumn('wallets', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        if (!$hadCurrentBalance && $hadLegacyBalance) {
            DB::statement('UPDATE `wallets` SET `current_balance` = `balance`');
        }

        if (
            Schema::hasColumn('wallets', 'tenant_id')
            && !$this->foreignKeyExists('wallets', 'wallets_tenant_id_foreign')
            && !$this->hasOrphanReferences('wallets', 'tenant_id', 'tenants', 'id')
        ) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            });
        }

        if (
            Schema::hasColumn('wallets', 'member_id')
            && !$this->foreignKeyExists('wallets', 'wallets_member_id_foreign')
            && !$this->hasOrphanReferences('wallets', 'member_id', 'members', 'id')
        ) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();
            });
        }

        if (
            Schema::hasColumn('wallets', 'created_by')
            && Schema::hasTable('users')
            && !$this->foreignKeyExists('wallets', 'wallets_created_by_foreign')
            && !$this->hasOrphanReferences('wallets', 'created_by', 'users', 'id')
        ) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (
            Schema::hasColumn('wallets', 'updated_by')
            && Schema::hasTable('users')
            && !$this->foreignKeyExists('wallets', 'wallets_updated_by_foreign')
            && !$this->hasOrphanReferences('wallets', 'updated_by', 'users', 'id')
        ) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (
            Schema::hasColumn('wallets', 'member_id')
            && !$this->indexExists('wallets', 'wallets_member_id_unique')
            && !$this->hasDuplicateMemberIds()
        ) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->unique('member_id');
            });
        }

        if (
            Schema::hasColumn('wallets', 'tenant_id')
            && Schema::hasColumn('wallets', 'status')
            && !$this->indexExists('wallets', 'wallets_tenant_id_status_index')
        ) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->index(['tenant_id', 'status']);
            });
        }
    }

    private function hasDuplicateMemberIds(): bool
    {
        return DB::table('wallets')
            ->whereNotNull('member_id')
            ->select('member_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('member_id')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();
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
