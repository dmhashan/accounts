<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ManagerNotificationDelivery;
use App\Models\ManagerNotificationRecipient;
use App\Models\ManagerNotificationSetting;
use App\Services\ManagerNotificationCatalog;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ManagerNotificationApiController extends Controller
{
    public function index(): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $settings = ManagerNotificationSetting::query()->firstOrCreate(
            ['tenant_id' => $tenantId],
            ['email_events' => [], 'sms_events' => []],
        );

        return response()->json([
            'data' => [
                'settings' => [
                    'email_enabled' => (bool) $settings->email_enabled,
                    'sms_enabled' => (bool) $settings->sms_enabled,
                    'email_events' => $settings->email_events ?? [],
                    'sms_events' => $settings->sms_events ?? [],
                ],
                'events' => ManagerNotificationCatalog::all(),
                'recipients' => ManagerNotificationRecipient::query()
                    ->where('tenant_id', $tenantId)
                    ->orderBy('channel')
                    ->orderBy('label')
                    ->orderBy('id')
                    ->get(['id', 'channel', 'label', 'destination']),
                'deliveries' => ManagerNotificationDelivery::query()
                    ->where('tenant_id', $tenantId)
                    ->latest('id')
                    ->limit(20)
                    ->get(['id', 'event_key', 'channel', 'destination', 'status', 'summary', 'error_message', 'sent_at', 'created_at']),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_enabled' => ['sometimes', 'boolean'],
            'sms_enabled' => ['sometimes', 'boolean'],
            'email_events' => ['sometimes', 'array'],
            'email_events.*' => ['string', Rule::in(ManagerNotificationCatalog::keys())],
            'sms_events' => ['sometimes', 'array'],
            'sms_events.*' => ['string', Rule::in(ManagerNotificationCatalog::keys())],
        ]);

        $tenantId = (int) app('tenant')->id;
        $settings = ManagerNotificationSetting::query()->firstOrNew(['tenant_id' => $tenantId]);
        $settings->fill($validated);
        $settings->tenant_id = $tenantId;
        $settings->save();

        return response()->json([
            'message' => 'Manager notification settings saved.',
            'data' => [
                'email_enabled' => (bool) $settings->email_enabled,
                'sms_enabled' => (bool) $settings->sms_enabled,
                'email_events' => $settings->email_events ?? [],
                'sms_events' => $settings->sms_events ?? [],
            ],
        ]);
    }

    public function storeRecipient(Request $request): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $channel = (string) $request->input('channel', '');
        $destination = $this->normalizeDestination($channel, (string) $request->input('destination', ''));
        $validated = $this->validateRecipient($request, $tenantId, $channel, $destination);

        $recipient = ManagerNotificationRecipient::query()->create([
            'tenant_id' => $tenantId,
            'channel' => $channel,
            'label' => $validated['label'] ?? null,
            'destination' => $destination,
            'normalized_destination' => $destination,
        ]);

        return response()->json([
            'message' => 'Recipient added.',
            'data' => $recipient->only(['id', 'channel', 'label', 'destination']),
        ], 201);
    }

    public function updateRecipient(Request $request, int $recipient): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $model = ManagerNotificationRecipient::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($recipient);
        $request->merge(['channel' => $model->channel]);
        $destination = $this->normalizeDestination($model->channel, (string) $request->input('destination', ''));
        $validated = $this->validateRecipient($request, $tenantId, $model->channel, $destination, $model->id);

        $model->update([
            'label' => $validated['label'] ?? null,
            'destination' => $destination,
            'normalized_destination' => $destination,
        ]);

        return response()->json([
            'message' => 'Recipient updated.',
            'data' => $model->only(['id', 'channel', 'label', 'destination']),
        ]);
    }

    public function destroyRecipient(int $recipient): JsonResponse
    {
        ManagerNotificationRecipient::query()
            ->where('tenant_id', (int) app('tenant')->id)
            ->findOrFail($recipient)
            ->delete();

        return response()->json(['message' => 'Recipient removed.']);
    }

    private function validateRecipient(Request $request, int $tenantId, string $channel, string $destination, ?int $ignoreId = null): array
    {
        $rules = [
            'channel' => ['required', 'in:email,sms'],
            'label' => ['nullable', 'string', 'max:100'],
            'destination' => ['required', 'string', 'max:255'],
        ];

        if ($channel === 'email') {
            $rules['destination'][] = 'email:rfc';
        } elseif ($channel === 'sms') {
            $rules['destination'][] = 'regex:/^\+[1-9]\d{7,14}$/';
        }

        $unique = Rule::unique('manager_notification_recipients', 'normalized_destination')
            ->where(fn (Builder $query) => $query->where('tenant_id', $tenantId)->where('channel', $channel));

        if ($ignoreId !== null) {
            $unique->ignore($ignoreId);
        }

        $rules['destination'][] = $unique;
        $data = array_merge($request->all(), ['destination' => $destination]);

        return Validator::make($data, $rules, [
            'destination.regex' => 'Enter the phone number in international format, for example +94771234567.',
            'destination.unique' => 'This destination is already in the recipient list.',
        ])->validate();
    }

    private function normalizeDestination(string $channel, string $destination): string
    {
        $destination = trim($destination);

        if ($channel === 'email') {
            return mb_strtolower($destination);
        }

        return preg_replace('/[\s().-]+/', '', $destination) ?? $destination;
    }
}
