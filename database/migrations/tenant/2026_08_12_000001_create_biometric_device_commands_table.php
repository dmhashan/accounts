<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_device_commands', function (Blueprint $table) {
            $table->id();
            $table->string('device_sn', 50)->index();
            $table->string('command_type', 50)->default('DATA USER');
            $table->text('command_string');
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('biometric_member_id', 50)->nullable();
            $table->string('action', 50)->nullable();
            $table->integer('return_code')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['device_sn', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_device_commands');
    }
};
