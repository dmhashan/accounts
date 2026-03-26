<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_account_id')->constrained('company_accounts')->restrictOnDelete();
            $table->string('category');
            $table->decimal('amount', 14, 2);
            $table->date('expense_date');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'expense_date'], 'expenses_tenant_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
