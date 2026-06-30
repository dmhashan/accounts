<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameEmployeeColumns();
        $this->renamePaySheetTables();
        $this->renamePaySheetItemColumn();
        $this->renameHistoricalReferences();
        $this->renamePermission();
    }

    public function down(): void
    {
        $this->restorePermission();
        $this->restoreHistoricalReferences();
        $this->restorePaySheetItemColumn();
        $this->renameTableIfNeeded('employee_pay_sheet_items', 'payroll_items');
        $this->renameTableIfNeeded('employee_pay_sheet_runs', 'payroll_runs');
        $this->renameColumnIfNeeded('employees', 'pay_sheet_notes', 'payroll_notes');
    }

    private function renameEmployeeColumns(): void
    {
        $this->renameColumnIfNeeded('employees', 'payroll_notes', 'pay_sheet_notes');
    }

    private function renamePaySheetTables(): void
    {
        $this->dropForeignIfExists('payroll_items', 'payroll_items_payroll_run_id_foreign');
        $this->dropForeignIfExists('employee_pay_sheet_items', 'payroll_items_payroll_run_id_foreign');

        $this->renameTableIfNeeded('payroll_runs', 'employee_pay_sheet_runs');
        $this->renameTableIfNeeded('payroll_items', 'employee_pay_sheet_items');
    }

    private function renamePaySheetItemColumn(): void
    {
        $this->renameColumnIfNeeded('employee_pay_sheet_items', 'payroll_run_id', 'employee_pay_sheet_run_id');

        if (
            Schema::hasTable('employee_pay_sheet_items')
            && Schema::hasTable('employee_pay_sheet_runs')
            && Schema::hasColumn('employee_pay_sheet_items', 'employee_pay_sheet_run_id')
            && !$this->foreignKeyOnColumnExists('employee_pay_sheet_items', 'employee_pay_sheet_run_id')
        ) {
            Schema::table('employee_pay_sheet_items', function (Blueprint $table) {
                $table->foreign('employee_pay_sheet_run_id')
                    ->references('id')
                    ->on('employee_pay_sheet_runs')
                    ->cascadeOnDelete();
            });
        }
    }

    private function restorePaySheetItemColumn(): void
    {
        $this->dropForeignIfExists(
            'employee_pay_sheet_items',
            'employee_pay_sheet_items_employee_pay_sheet_run_id_foreign',
        );

        $this->renameColumnIfNeeded('employee_pay_sheet_items', 'employee_pay_sheet_run_id', 'payroll_run_id');

        if (
            Schema::hasTable('employee_pay_sheet_items')
            && Schema::hasTable('employee_pay_sheet_runs')
            && Schema::hasColumn('employee_pay_sheet_items', 'payroll_run_id')
            && !$this->foreignKeyOnColumnExists('employee_pay_sheet_items', 'payroll_run_id')
        ) {
            Schema::table('employee_pay_sheet_items', function (Blueprint $table) {
                $table->foreign('payroll_run_id', 'payroll_items_payroll_run_id_foreign')
                    ->references('id')
                    ->on('employee_pay_sheet_runs')
                    ->cascadeOnDelete();
            });
        }
    }

    private function renameHistoricalReferences(): void
    {
        if (Schema::hasTable('employee_documents') && Schema::hasColumn('employee_documents', 'category')) {
            DB::table('employee_documents')
                ->where('category', 'payroll')
                ->update(['category' => 'pay_sheet']);
        }

        if (Schema::hasTable('company_account_transactions')) {
            if (Schema::hasColumn('company_account_transactions', 'model_name')) {
                DB::table('company_account_transactions')
                    ->where('model_name', 'payroll')
                    ->update(['model_name' => 'employee_pay_sheet']);
            }

            if (Schema::hasColumn('company_account_transactions', 'type')) {
                DB::table('company_account_transactions')
                    ->where('type', 'payroll')
                    ->update(['type' => 'employee_pay_sheet']);
            }
        }
    }

    private function restoreHistoricalReferences(): void
    {
        if (Schema::hasTable('employee_documents') && Schema::hasColumn('employee_documents', 'category')) {
            DB::table('employee_documents')
                ->where('category', 'pay_sheet')
                ->update(['category' => 'payroll']);
        }

        if (Schema::hasTable('company_account_transactions')) {
            if (Schema::hasColumn('company_account_transactions', 'model_name')) {
                DB::table('company_account_transactions')
                    ->where('model_name', 'employee_pay_sheet')
                    ->update(['model_name' => 'payroll']);
            }

            if (Schema::hasColumn('company_account_transactions', 'type')) {
                DB::table('company_account_transactions')
                    ->where('type', 'employee_pay_sheet')
                    ->update(['type' => 'payroll']);
            }
        }
    }

    private function renamePermission(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('role_permission')) {
            return;
        }

        $timestamp = now();
        $newPermissionId = DB::table('permissions')
            ->where('slug', 'employee_pay_sheets.manage')
            ->value('id');

        if ($newPermissionId) {
            DB::table('permissions')
                ->where('id', $newPermissionId)
                ->update([
                    'name' => 'Manage Employee Pay Sheet',
                    'feature' => 'Employee Pay Sheet',
                    'description' => 'Generate and manage employee pay sheet runs.',
                    'updated_at' => $timestamp,
                ]);
        } else {
            $newPermissionId = DB::table('permissions')->insertGetId([
                'name' => 'Manage Employee Pay Sheet',
                'slug' => 'employee_pay_sheets.manage',
                'feature' => 'Employee Pay Sheet',
                'description' => 'Generate and manage employee pay sheet runs.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        $oldPermissionId = DB::table('permissions')
            ->where('slug', 'payroll.manage')
            ->value('id');

        if ($oldPermissionId && (int) $oldPermissionId !== (int) $newPermissionId) {
            $rows = DB::table('role_permission')
                ->where('permission_id', $oldPermissionId)
                ->pluck('role_id')
                ->map(fn ($roleId) => [
                    'role_id' => $roleId,
                    'permission_id' => $newPermissionId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])
                ->all();

            if ($rows !== []) {
                DB::table('role_permission')->insertOrIgnore($rows);
            }

            DB::table('role_permission')->where('permission_id', $oldPermissionId)->delete();
            DB::table('permissions')->where('id', $oldPermissionId)->delete();
        }

        if (Schema::hasTable('roles')) {
            $rows = DB::table('roles')
                ->whereIn('slug', ['super-admin', 'admin', 'owner'])
                ->pluck('id')
                ->map(fn ($roleId) => [
                    'role_id' => $roleId,
                    'permission_id' => $newPermissionId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])
                ->all();

            if ($rows !== []) {
                DB::table('role_permission')->insertOrIgnore($rows);
            }
        }
    }

    private function restorePermission(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('role_permission')) {
            return;
        }

        $timestamp = now();
        $oldPermissionId = DB::table('permissions')
            ->where('slug', 'payroll.manage')
            ->value('id');

        if (!$oldPermissionId) {
            $oldPermissionId = DB::table('permissions')->insertGetId([
                'name' => 'Manage Payroll',
                'slug' => 'payroll.manage',
                'feature' => 'Payroll',
                'description' => 'Generate and manage payroll runs.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        $newPermissionId = DB::table('permissions')
            ->where('slug', 'employee_pay_sheets.manage')
            ->value('id');

        if (!$newPermissionId || (int) $newPermissionId === (int) $oldPermissionId) {
            return;
        }

        $rows = DB::table('role_permission')
            ->where('permission_id', $newPermissionId)
            ->pluck('role_id')
            ->map(fn ($roleId) => [
                'role_id' => $roleId,
                'permission_id' => $oldPermissionId,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('role_permission')->insertOrIgnore($rows);
        }

        DB::table('role_permission')->where('permission_id', $newPermissionId)->delete();
        DB::table('permissions')->where('id', $newPermissionId)->delete();
    }

    private function renameTableIfNeeded(string $from, string $to): void
    {
        if (Schema::hasTable($from) && !Schema::hasTable($to)) {
            Schema::rename($from, $to);
        }
    }

    private function renameColumnIfNeeded(string $table, string $from, string $to): void
    {
        if (Schema::hasTable($table) && Schema::hasColumn($table, $from) && !Schema::hasColumn($table, $to)) {
            Schema::table($table, function (Blueprint $table) use ($from, $to) {
                $table->renameColumn($from, $to);
            });
        }
    }

    private function dropForeignIfExists(string $table, string $foreignKey): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        if (DB::connection()->getDriverName() === 'mysql' && !$this->foreignKeyExists($table, $foreignKey)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $table) use ($foreignKey) {
                $table->dropForeign($foreignKey);
            });
        } catch (Throwable) {
            //
        }
    }

    private function foreignKeyOnColumnExists(string $table, string $column): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();
    }

    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $foreignKey)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
