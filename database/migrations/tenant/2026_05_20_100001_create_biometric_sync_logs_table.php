<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('direction', ['up', 'down']);
            $table->enum('action', ['create', 'update', 'delete', 'attendance', 'manual_sync', 'test']);
            $table->enum('status', ['success', 'failed']);
            $table->string('device_maker')->default('');
            $table->string('device_model')->default('');
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('synced_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'member_id']);
            $table->index(['tenant_id', 'direction', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_sync_logs');
    }
};
