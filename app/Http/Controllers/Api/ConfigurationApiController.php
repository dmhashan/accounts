<?php

namespace App\Http\Controllers\Api;

use App\Enums\DateFormat;
use App\Enums\TimeFormat;
use App\Http\Controllers\Controller;
use App\Services\TenantConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Enum;

class ConfigurationApiController extends Controller
{
    public function __construct(
        private readonly TenantConfigurationService $service,
    ) {}

    public function index(): JsonResponse
    {
        $tenant = app('tenant');
        $all = $this->service->all($tenant->id);
        $next = \App\Models\Member::generateNextIds($tenant->id);

        return response()->json([
            'data' => array_merge($all, [
                '_next_member_id' => $next['next_member_id'],
                '_next_biometric_id' => $next['next_biometric_id'],
            ]),
        ]);
    }

    public function formatOptions(): JsonResponse
    {
        return response()->json([
            'date_formats' => DateFormat::options(),
            'time_formats' => TimeFormat::options(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            // General display preferences
            'general\.date_format' => ['sometimes', new Enum(DateFormat::class)],
            'general\.time_format' => ['sometimes', new Enum(TimeFormat::class)],
            'general\.color_theme' => ['sometimes', 'in:crimson,ocean,forest,violet,sunset,slate'],
            'general\.color_mode' => ['sometimes', 'in:system,light,dark'],
            'general\.member_notifications' => ['sometimes', 'nullable', 'json'],
            'general\.gowa_enabled' => ['sometimes', 'in:0,1'],
            'general\.gowa_url' => ['sometimes', 'nullable', 'string', 'max:500'],
            'general\.gowa_api_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'general\.gowa_session_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'general\.gowa_groups' => ['sometimes', 'nullable', 'json'],
            'body_measurements\.fields' => ['sometimes', 'nullable', 'json'],

            'notifications.inapp.enabled' => ['sometimes', 'in:0,1'],
            'notifications.email.enabled' => ['sometimes', 'in:0,1'],
            'notifications.email.smtp_host' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notifications.email.smtp_port' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
            'notifications.email.smtp_username' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notifications.email.smtp_password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notifications.email.smtp_encryption' => ['sometimes', 'nullable', 'in:tls,ssl,none'],
            'notifications.email.from_address' => ['sometimes', 'nullable', 'email', 'max:255'],
            'notifications.email.from_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notifications.sms.enabled' => ['sometimes', 'in:0,1'],
            'notifications.sms.user_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notifications.sms.api_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notifications.sms.sender_id' => ['sometimes', 'nullable', 'string', 'max:50'],

            // Biometric device
            'biometric.enabled' => ['sometimes', 'in:0,1'],
            'biometric.device_maker' => ['sometimes', 'nullable', 'string', 'max:100'],
            'biometric.device_model' => ['sometimes', 'nullable', 'string', 'max:100'],
            'biometric.device_ip' => ['sometimes', 'nullable', 'string', 'max:255'],
            'biometric.device_port' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
            'biometric.device_username' => ['sometimes', 'nullable', 'string', 'max:100'],
            'biometric.device_password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'biometric.sync_members' => ['sometimes', 'in:0,1'],
            'biometric.access_control' => ['sometimes', 'in:0,1'],
            'biometric.grace_period_days' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:365'],
            'biometric.webhook_enabled' => ['sometimes', 'in:0,1'],
            'biometric.webhook_server_host' => ['sometimes', 'nullable', 'string', 'max:255'],
            'biometric.webhook_server_port' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
            'biometric.access_events_sync_from' => ['sometimes', 'nullable', 'date'],
            'biometric.device_sn' => ['sometimes', 'nullable', 'string', 'max:100'],
            'biometric.zk_fingerprint_alg' => ['sometimes', 'nullable', 'string', 'max:50'],
            'biometric.zk_face_alg' => ['sometimes', 'nullable', 'string', 'max:50'],
            'biometric.adms_delay' => ['sometimes', 'nullable', 'integer', 'min:3', 'max:3600'],

            // Member ID & Biometric ID format preferences
            'member.id_prefix' => ['sometimes', 'nullable', 'string', 'max:20'],
            'member.id_next_number' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'member.id_padding' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:10'],
            'member.id_auto_generate' => ['sometimes', 'in:0,1'],
            'biometric.id_prefix' => ['sometimes', 'nullable', 'string', 'max:20'],
            'biometric.id_next_number' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'biometric.id_padding' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:10'],
            'biometric.id_same_as_member_id' => ['sometimes', 'in:0,1'],
        ]);

        $tenant = app('tenant');

        $biometricWebhookKeys = array_intersect_key($request->all(), array_flip([
            'biometric.webhook_enabled',
            'biometric.webhook_server_host',
            'biometric.webhook_server_port',
        ]));

        if ($biometricWebhookKeys !== []) {
            Log::debug('Biometric real-time push: settings update requested', [
                'settings' => $biometricWebhookKeys,
            ]);
        }

        $data = $this->service->updateBatch($tenant->id, $request->all());

        if ($biometricWebhookKeys !== []) {
            Log::debug('Biometric real-time push: settings update saved', [
                'settings' => array_intersect_key($data, $biometricWebhookKeys),
            ]);
        }

        $next = \App\Models\Member::generateNextIds($tenant->id);

        return response()->json([
            'message' => 'Configuration saved successfully.',
            'data' => array_merge($data, [
                '_next_member_id' => $next['next_member_id'],
                '_next_biometric_id' => $next['next_biometric_id'],
            ]),
        ]);
    }
}
