<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->enum('type', ['account', 'stock']);
            $table->unsignedBigInteger('reference_id'); // company_accounts.id or products.id
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'role_id', 'type', 'reference_id'], 'reconciliation_configs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_configs');
    }
};
