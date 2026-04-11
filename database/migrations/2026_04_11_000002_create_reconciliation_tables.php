<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->foreignId('opened_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('adjustment_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'date']);
        });

        Schema::create('reconciliation_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('reconciliation_sessions')->cascadeOnDelete();
            $table->enum('type', ['account', 'stock']);
            $table->unsignedBigInteger('reference_id');
            $table->enum('stage', ['open', 'close']);
            $table->decimal('entered_value', 14, 2);
            $table->timestamps();

            $table->unique(['session_id', 'type', 'reference_id', 'stage'], 'reconciliation_entries_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_entries');
        Schema::dropIfExists('reconciliation_sessions');
    }
};
