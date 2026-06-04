<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE `biometric_sync_logs`
             MODIFY COLUMN `action` ENUM(
                 'create','update','delete','attendance','manual_sync','test',
                 'face_setup','fingerprint_setup',
                 'webhook_configure','webhook_event'
             ) NOT NULL",
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Remove rows with new action values before reverting
        DB::statement("DELETE FROM `biometric_sync_logs` WHERE `action` IN ('webhook_configure','webhook_event')");

        DB::statement(
            "ALTER TABLE `biometric_sync_logs`
             MODIFY COLUMN `action` ENUM(
                 'create','update','delete','attendance','manual_sync','test',
                 'face_setup','fingerprint_setup'
             ) NOT NULL",
        );
    }
};
