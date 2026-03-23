<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_account_transactions')) {
            return;
        }

        Schema::create('company_account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_account_id')->constrained('company_accounts')->restrictOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->string('type');
            $table->decimal('amount', 14, 2);
            $table->date('transaction_date');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'company_account_id', 'type'], 'cat_tenant_account_type_idx');
            $table->unique(['sale_id', 'type'], 'cat_sale_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_account_transactions');
    }
};

