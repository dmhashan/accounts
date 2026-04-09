<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('member_id')->nullable()->index();
            $table->string('session_id', 64)->index();

            // What happened
            $table->string('event_type', 50)->index();   // otp_requested, otp_verified, login, logout, tab_view, workout_opened, sale_opened
            $table->string('section', 100)->nullable();  // tab name, workout title, etc.

            // Network / device info (collected server-side from HTTP headers)
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 20)->nullable();  // mobile | tablet | desktop
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();

            // Client-provided display metrics
            $table->unsignedSmallInteger('screen_width')->nullable();
            $table->unsignedSmallInteger('screen_height')->nullable();

            // Arbitrary extra context (workout_id, sale_id, etc.)
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_activity_logs');
    }
};
