<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('legacy_uuid')->nullable()->index();
            $table->unsignedInteger('legacy_member_id')->nullable()->index();
            $table->string('username')->nullable();
            $table->date('attended_date')->index();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('member_id')->references('id')->on('members')->nullOnDelete();

            // One record per member per day (keyed by legacy UUID + date)
            $table->unique(['tenant_id', 'legacy_uuid', 'attended_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_attendances');
    }
};
