<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OpenWaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpenWaApiController extends Controller
{
    public function __construct(
        private readonly OpenWaService $openWaService,
    ) {}

    /**
     * Test connection to OpenWA server instance.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'url'],
            'api_key' => ['nullable', 'string'],
            'session_id' => ['nullable', 'string'],
        ]);

        $result = $this->openWaService->testConnection(
            $validated['url'],
            $validated['api_key'] ?? null,
            $validated['session_id'] ?? null,
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Compare system members against OpenWA group members.
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

        $result = $this->openWaService->compareMembers(
            $validated['group'],
            $validated['url'],
            $validated['api_key'] ?? null,
            $validated['session_id'] ?? null,
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Execute bulk add or remove action for an OpenWA group.
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
        ]);

        if ($validated['action'] === 'add') {
            $result = $this->openWaService->addParticipants(
                $validated['url'],
                $validated['group_id'],
                $validated['phones'],
                $validated['api_key'] ?? null,
                $validated['session_id'] ?? null,
            );
        } else {
            $result = $this->openWaService->removeParticipants(
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
