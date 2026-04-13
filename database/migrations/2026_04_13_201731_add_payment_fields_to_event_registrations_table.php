<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            // is_paid may have been added in a previous partial run; skip if present
            if (! Schema::hasColumn('event_registrations', 'is_paid')) {
                $table->boolean('is_paid')->default(false)->after('total_fee');
            }
            if (! Schema::hasColumn('event_registrations', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('is_paid');
            }
            if (! Schema::hasColumn('event_registrations', 'company_account_id')) {
                $table->foreignId('company_account_id')->nullable()->constrained('company_accounts')->nullOnDelete()->after('paid_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('event_registrations', 'company_account_id')) {
                $table->dropForeign(['company_account_id']);
                $table->dropColumn('company_account_id');
            }
            if (Schema::hasColumn('event_registrations', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
            if (Schema::hasColumn('event_registrations', 'is_paid')) {
                $table->dropColumn('is_paid');
            }
        });
    }
};
