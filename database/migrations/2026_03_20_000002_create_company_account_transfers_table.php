<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_account_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_account_id')->constrained('company_accounts')->restrictOnDelete();
            $table->foreignId('destination_account_id')->constrained('company_accounts')->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('transfer_date');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'transfer_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_account_transfers');
    }
};