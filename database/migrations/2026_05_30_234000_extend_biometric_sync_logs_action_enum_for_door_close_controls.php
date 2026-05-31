<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE `biometric_sync_logs`
             MODIFY COLUMN `action` ENUM(
                 'create','update','delete','attendance','manual_sync','test',
                 'face_setup','fingerprint_setup',
                 'webhook_configure','webhook_event',
                 'unlock','keep_unlock','close','keep_close'
             ) NOT NULL",
        );
    }

    public function down(): void
    {
        DB::statement("DELETE FROM `biometric_sync_logs` WHERE `action` IN ('close','keep_close')");

        DB::statement(
            "ALTER TABLE `biometric_sync_logs`
             MODIFY COLUMN `action` ENUM(
                 'create','update','delete','attendance','manual_sync','test',
                 'face_setup','fingerprint_setup',
                 'webhook_configure','webhook_event',
                 'unlock','keep_unlock'
             ) NOT NULL",
        );
    }
};
