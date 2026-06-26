<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('message');
            $table->enum('status', ['draft', 'sent'])->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bulk_notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_notification_id')->constrained('bulk_notifications')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('phone_number', 50);
            $table->timestamps();

            $table->unique(['bulk_notification_id', 'member_id'], 'bnr_notification_member_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_notification_recipients');
        Schema::dropIfExists('bulk_notifications');
    }
};
