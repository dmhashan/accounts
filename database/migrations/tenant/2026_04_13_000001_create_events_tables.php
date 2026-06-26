<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('slug', 150);
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime')->nullable();
            $table->string('venue', 255)->nullable();
            $table->text('agenda')->nullable();
            $table->text('registration_process')->nullable();
            $table->decimal('ticket_fee', 10, 2)->default(0);
            $table->decimal('additional_ticket_fee', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'slug'], 'events_tenant_slug_unique');
        });

        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_fee', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('event_registration_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_registration_id')->constrained('event_registrations')->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->decimal('fee', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registration_guests');
        Schema::dropIfExists('event_registrations');
        Schema::dropIfExists('events');
    }
};
