<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->boolean('email_enabled')->default(false);
            $table->boolean('sms_enabled')->default(false);
            $table->json('email_events')->nullable();
            $table->json('sms_events')->nullable();
            $table->timestamps();
            $table->unique('tenant_id');
        });

        Schema::create('manager_notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('channel', 10);
            $table->string('label', 100)->nullable();
            $table->string('destination', 255);
            $table->string('normalized_destination', 255);
            $table->timestamps();
            $table->unique(['tenant_id', 'channel', 'normalized_destination'], 'manager_recipient_unique_destination');
            $table->index(['tenant_id', 'channel']);
        });

        Schema::create('manager_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('event_uuid', 36);
            $table->string('event_key', 60);
            $table->string('channel', 10);
            $table->string('destination', 255);
            $table->string('destination_hash', 64);
            $table->string('status', 12)->default('pending');
            $table->text('summary');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'event_uuid', 'channel', 'destination_hash'], 'manager_delivery_idempotency');
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_notification_deliveries');
        Schema::dropIfExists('manager_notification_recipients');
        Schema::dropIfExists('manager_notification_settings');
    }
};
