<?php

use App\Models\Member;
use App\Services\BiometricSyncService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Backfill has_face directly from biometric access events, profile photo, and sync logs
        DB::statement("
            UPDATE members 
            SET has_face = 1 
            WHERE (profile_photo_path IS NOT NULL AND profile_photo_path != '')
               OR EXISTS (
                   SELECT 1 FROM biometric_access_events 
                   WHERE biometric_access_events.member_id = members.id 
                     AND (auth_method = 'face' OR auth_method LIKE '%face%')
               )
               OR EXISTS (
                   SELECT 1 FROM biometric_sync_logs 
                   WHERE biometric_sync_logs.member_id = members.id 
                     AND action IN ('upload_face_photo', 'face_setup', 'upload_face') 
                     AND status = 'success'
               )
        ");

        // 2. Backfill has_fingerprint directly from biometric access events, fingerprint setup logs, and sync timestamps
        DB::statement("
            UPDATE members 
            SET has_fingerprint = 1 
            WHERE EXISTS (
                   SELECT 1 FROM biometric_access_events 
                   WHERE biometric_access_events.member_id = members.id 
                     AND (auth_method = 'fingerprint' OR auth_method LIKE '%finger%' OR auth_method = 'fp')
               )
               OR EXISTS (
                   SELECT 1 FROM biometric_sync_logs 
                   WHERE biometric_sync_logs.member_id = members.id 
                     AND action = 'fingerprint_setup' 
                     AND status = 'success'
               )
               OR biometric_last_synced_at IS NOT NULL
        ");

        // 3. Attempt direct biometric device queries for members with biometric IDs
        if (app()->bound(BiometricSyncService::class)) {
            try {
                /** @var BiometricSyncService $service */
                $service = app(BiometricSyncService::class);
                Member::query()
                    ->whereNotNull('biometric_member_id')
                    ->where('biometric_member_id', '!=', '')
                    ->chunk(50, function ($members) use ($service) {
                        foreach ($members as $member) {
                            try {
                                $service->getMemberDeviceInfo($member);
                            } catch (Throwable $e) {
                                // Ignore device connection errors during migration backfill
                            }
                        }
                    });
            } catch (Throwable $e) {
                // Ignore service resolution issues during migration
            }
        }
    }

    public function down(): void
    {
        // No-op
    }
};
