<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_attendances', function (Blueprint $table) {
            $table->foreignId('biometric_access_event_id')
                ->nullable()
                ->after('member_id')
                ->constrained('biometric_access_events')
                ->nullOnDelete();

            $table->index(['tenant_id', 'biometric_access_event_id'], 'member_attendance_tenant_bio_event_idx');
        });
    }

    public function down(): void
    {
        Schema::table('member_attendances', function (Blueprint $table) {
            $table->dropIndex('member_attendance_tenant_bio_event_idx');
            $table->dropConstrainedForeignId('biometric_access_event_id');
        });
    }
};
