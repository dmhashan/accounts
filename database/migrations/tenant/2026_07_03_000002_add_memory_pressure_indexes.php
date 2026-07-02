<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->index('bulk_notifications', 'bn_created_id_idx', ['created_at', 'id']);
        $this->index('bulk_notification_recipients', 'bnr_notification_id_idx', ['bulk_notification_id', 'id']);
        $this->index('company_account_transactions', 'cat_date_id_idx', ['transaction_date', 'id']);
        $this->index('member_activity_logs', 'mal_created_id_idx', ['created_at', 'id']);
        $this->index('member_notifications', 'mn_member_created_idx', ['member_id', 'created_at']);
        $this->index('member_payments', 'mp_member_method_created_idx', ['member_id', 'payment_method', 'created_at']);
        $this->index('member_payments', 'mp_payment_date_id_idx', ['payment_date', 'id']);
        $this->index('sales', 'sales_created_id_idx', ['created_at', 'id']);
        $this->index('sales', 'sales_wallet_member_created_idx', ['customer_member_id', 'payment_method', 'deleted_at', 'created_at']);
        $this->index('wallet_topups', 'wt_member_created_idx', ['member_id', 'created_at']);
    }

    public function down(): void
    {
        $this->dropIndex('wallet_topups', 'wt_member_created_idx');
        $this->dropIndex('sales', 'sales_wallet_member_created_idx');
        $this->dropIndex('sales', 'sales_created_id_idx');
        $this->dropIndex('member_payments', 'mp_payment_date_id_idx');
        $this->dropIndex('member_payments', 'mp_member_method_created_idx');
        $this->dropIndex('member_notifications', 'mn_member_created_idx');
        $this->dropIndex('member_activity_logs', 'mal_created_id_idx');
        $this->dropIndex('company_account_transactions', 'cat_date_id_idx');
        $this->dropIndex('bulk_notification_recipients', 'bnr_notification_id_idx');
        $this->dropIndex('bulk_notifications', 'bn_created_id_idx');
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function index(string $table, string $name, array $columns): void
    {
        if (!Schema::hasTable($table) || $this->hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($name, $columns): void {
            $table->index($columns, $name);
        });
    }

    private function dropIndex(string $table, string $name): void
    {
        if (!Schema::hasTable($table) || !$this->hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($name): void {
            $table->dropIndex($name);
        });
    }

    private function hasIndex(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))
            ->pluck('name')
            ->contains($name);
    }
};
