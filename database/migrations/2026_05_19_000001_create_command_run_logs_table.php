<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('command_run_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('command');
            $table->json('params');
            $table->tinyInteger('exit_code')->nullable();
            $table->longText('output')->nullable();
            $table->boolean('success')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'command']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('command_run_logs');
    }
};
