<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\TenantConfigurationService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppApiController extends Controller
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService,
        private readonly TenantConfigurationService $configService,
    ) {}

    /**
     * Get current WhatsApp integration configuration.
     */
    public function getConfig(Request $request): JsonResponse
    {
        $config = $this->whatsAppService->getConfig();

        return response()->json([
            'success' => true,
            'data' => $config,
        ]);
    }

    /**
     * Update WhatsApp integration configuration.
     */
    public function updateConfig(Request $request): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'url' => ['nullable', 'string', 'max:500'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'session_id' => ['nullable', 'string', 'max:255'],
        ]);

        $this->configService->updateBatch($tenantId, [
            'general.gowa_enabled' => $validated['enabled'] ? '1' : '0',
            'general.gowa_url' => $validated['url'] ?? '',
            'general.gowa_api_key' => $validated['api_key'] ?? '',
            'general.gowa_session_id' => $validated['session_id'] ?? '',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp configuration updated successfully.',
            'data' => $this->whatsAppService->getConfig($tenantId),
        ]);
    }

    /**
     * Test connection to the WhatsApp provider/server.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['nullable', 'string'],
            'api_key' => ['nullable', 'string'],
            'session_id' => ['nullable', 'string'],
        ]);

        $result = $this->whatsAppService->testConnection($validated);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Fetch WhatsApp message history for a member or phone number.
     */
    public function getMessages(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['nullable', 'string'],
            'member_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $phone = $validated['phone'] ?? null;

        if (empty($phone) && !empty($validated['member_id'])) {
            $member = Member::query()->find($validated['member_id']);

            if ($member) {
                $phone = $member->whatsapp_number ?: $member->phone_number;
            }
        }

        if (empty($phone)) {
            return response()->json([
                'success' => false,
                'message' => 'A valid phone number or member ID is required.',
                'messages' => [],
            ], 422);
        }

        $limit = (int) ($validated['limit'] ?? 50);
        $result = $this->whatsAppService->read($phone, $limit);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Send a WhatsApp message to a member or phone number.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['nullable', 'string'],
            'member_id' => ['nullable', 'integer'],
            'message' => ['required', 'string', 'max:4000'],
            'media_url' => ['nullable', 'string', 'url'],
            'media_type' => ['nullable', 'in:image,file,document,audio,video'],
            'caption' => ['nullable', 'string', 'max:1000'],
        ]);

        $phone = $validated['phone'] ?? null;

        if (empty($phone) && !empty($validated['member_id'])) {
            $member = Member::query()->find($validated['member_id']);

            if ($member) {
                $phone = $member->whatsapp_number ?: $member->phone_number;
            }
        }

        if (empty($phone)) {
            return response()->json([
                'success' => false,
                'message' => 'A valid phone number or member ID is required.',
            ], 422);
        }

        if (!empty($validated['media_url'])) {
            $result = $this->whatsAppService->sendMedia(
                $phone,
                $validated['media_url'],
                $validated['caption'] ?? $validated['message'],
                $validated['media_type'] ?? 'image',
            );
        } else {
            $result = $this->whatsAppService->send($phone, $validated['message']);
        }

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Check if a phone number or member is registered on WhatsApp.
     */
    public function checkUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['nullable', 'string'],
            'member_id' => ['nullable', 'integer'],
        ]);

        $phone = $validated['phone'] ?? null;

        if (empty($phone) && !empty($validated['member_id'])) {
            $member = Member::query()->find($validated['member_id']);

            if ($member) {
                $phone = $member->whatsapp_number ?: $member->phone_number;
            }
        }

        if (empty($phone)) {
            return response()->json([
                'success' => false,
                'message' => 'A valid phone number or member ID is required.',
            ], 422);
        }

        $result = $this->whatsAppService->checkUser($phone);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get login QR code for device pairing.
     */
    public function getLoginQr(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['nullable', 'string', 'url'],
            'api_key' => ['nullable', 'string'],
            'session_id' => ['nullable', 'string'],
        ]);

        $result = $this->whatsAppService->getLoginQr($validated);

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
