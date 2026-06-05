<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_access_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('biometric_member_id')->nullable();
            $table->string('employee_no')->nullable();
            $table->string('person_name')->nullable();
            // face | card | fingerprint | password | unknown
            $table->string('auth_method')->nullable();
            // success => marked as member attendance, failed => attempted
            $table->enum('result', ['success', 'failed']);
            $table->integer('minor_code')->nullable();
            // Stored snapshot captured by the device at authentication time
            $table->string('picture_path')->nullable();
            $table->timestamp('event_time')->nullable();
            $table->json('raw')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'result', 'event_time']);
            $table->index(['tenant_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_access_events');
    }
};
