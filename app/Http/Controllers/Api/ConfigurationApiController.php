<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TenantConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigurationApiController extends Controller
{
    public function __construct(
        private readonly TenantConfigurationService $service,
    ) {}

    public function index(): JsonResponse
    {
        $tenant = app('tenant');

        return response()->json([
            'data' => $this->service->all($tenant->id),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
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
            'biometric.sync_attendance' => ['sometimes', 'in:0,1'],
            'biometric.access_control' => ['sometimes', 'in:0,1'],
            'biometric.grace_period_days' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:365'],
        ]);

        $tenant = app('tenant');

        $data = $this->service->updateBatch($tenant->id, $request->all());

        return response()->json([
            'message' => 'Configuration saved successfully.',
            'data' => $data,
        ]);
    }
}
