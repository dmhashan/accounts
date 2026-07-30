<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncGoWaGroupJob;
use App\Services\GoWaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoWaApiController extends Controller
{
    public function __construct(
        private readonly GoWaService $goWaService,
    ) {}

    /**
     * Test connection to GoWA server instance via GET /app/info.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'url'],
            'api_key' => ['nullable', 'string'],
            'session_id' => ['nullable', 'string'],
        ]);

        $result = $this->goWaService->testConnection(
            $validated['url'],
            $validated['api_key'] ?? null,
            $validated['session_id'] ?? null,
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Compare system members against GoWA group members.
     */
    public function compareGroup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'url'],
            'api_key' => ['nullable', 'string'],
            'session_id' => ['nullable', 'string'],
            'group' => ['required', 'array'],
            'group.group_id' => ['required', 'string'],
            'group.rules' => ['sometimes', 'array'],
        ]);

        $result = $this->goWaService->compareMembers(
            $validated['group'],
            $validated['url'],
            $validated['api_key'] ?? null,
            $validated['session_id'] ?? null,
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Execute bulk add or remove action for a GoWA group.
     */
    public function syncGroup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'url'],
            'api_key' => ['nullable', 'string'],
            'session_id' => ['nullable', 'string'],
            'group_id' => ['required', 'string'],
            'action' => ['required', 'in:add,remove'],
            'phones' => ['required', 'array', 'min:1'],
            'phones.*' => ['required', 'string'],
            'async' => ['sometimes', 'boolean'],
        ]);

        $async = $validated['async'] ?? (count($validated['phones']) > 15);
        $tenantId = app()->bound('tenant') ? (int) app('tenant')->id : null;

        if ($async) {
            SyncGoWaGroupJob::dispatchForTenant(
                $tenantId,
                $validated['url'],
                $validated['group_id'],
                $validated['action'],
                $validated['phones'],
                $validated['api_key'] ?? null,
                $validated['session_id'] ?? null,
            );

            return response()->json([
                'success' => true,
                'async' => true,
                'message' => 'Group sync operation queued for background processing.',
                'action' => $validated['action'],
                'group_id' => $validated['group_id'],
                'queued_count' => count($validated['phones']),
            ], 202);
        }

        if ($validated['action'] === 'add') {
            $result = $this->goWaService->addParticipants(
                $validated['url'],
                $validated['group_id'],
                $validated['phones'],
                $validated['api_key'] ?? null,
                $validated['session_id'] ?? null,
            );
        } else {
            $result = $this->goWaService->removeParticipants(
                $validated['url'],
                $validated['group_id'],
                $validated['phones'],
                $validated['api_key'] ?? null,
                $validated['session_id'] ?? null,
            );
        }

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
