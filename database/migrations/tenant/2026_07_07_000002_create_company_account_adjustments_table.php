<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_account_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_account_id')->constrained('company_accounts')->restrictOnDelete();
            $table->string('type'); // 'credit' or 'debit'
            $table->decimal('amount', 14, 2);
            $table->text('reason');
            $table->date('adjustment_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_account_id', 'adjustment_date'], 'caa_account_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_account_adjustments');
    }
};
