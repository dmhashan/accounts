<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('company_account_id')->constrained('company_accounts')->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('topup_date');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'member_id']);
        });

        Schema::table('member_payments', function (Blueprint $table) {
            $table->string('payment_method')->default('cash')->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('member_payments', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });

        Schema::dropIfExists('wallet_topups');
    }
};
