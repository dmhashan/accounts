<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReconciliationSession;
use App\Services\ReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReconciliationApiController extends Controller
{
    public function __construct(
        private readonly ReconciliationService $reconciliationService,
    ) {}

    // ── Admin config ─────────────────────────────────────────────────────────

    public function config(): JsonResponse
    {
        return response()->json(
            $this->reconciliationService->getAdminConfig(app('tenant')->id)
        );
    }

    public function saveConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_id'              => ['required', 'integer', 'exists:roles,id'],
            'items'                => ['required', 'array'],
            'items.*.type'         => ['required', 'in:account,stock,stock_variation'],
            'items.*.reference_id' => ['required', 'integer'],
            'items.*.is_active'    => ['boolean'],
        ]);

        $this->reconciliationService->saveAdminConfig(
            app('tenant')->id,
            $validated['role_id'],
            $validated['items'],
        );

        return response()->json(['message' => 'Configuration saved.']);
    }

    // ── Session status ────────────────────────────────────────────────────────

    public function today(): JsonResponse
    {
        return response()->json([
            'session' => $this->reconciliationService->getTodaySession(app('tenant')->id),
        ]);
    }

    // ── Form config for the opening form ─────────────────────────────────────

    public function formConfig(Request $request): JsonResponse
    {
        $roleId = $request->user()->role_id;

        return response()->json(
            $this->reconciliationService->getFormConfig(app('tenant')->id, $roleId)
        );
    }

    // ── Open session ──────────────────────────────────────────────────────────

    public function open(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entries'                => ['required', 'array', 'min:1'],
            'entries.*.type'         => ['required', 'in:account,stock,stock_variation,stock_display,stock_variation_display'],
            'entries.*.reference_id' => ['required', 'integer'],
            'entries.*.entered_value' => ['required', 'numeric', 'min:0'],
        ]);

        $result = $this->reconciliationService->openSession(
            app('tenant')->id,
            $request->user()->id,
            $validated['entries'],
        );

        if (is_string($result)) {
            return response()->json(['message' => $result], 422);
        }

        return response()->json(['message' => 'Session opened.', 'session' => $result], 201);
    }

    // ── Save close entries without finalising ────────────────────────────────

    public function saveClose(Request $request, ReconciliationSession $session): JsonResponse
    {
        $this->guardSession($session);

        $validated = $request->validate([
            'entries'                 => ['required', 'array', 'min:1'],
            'entries.*.type'          => ['required', 'in:account,stock,stock_variation,stock_display,stock_variation_display'],
            'entries.*.reference_id'  => ['required', 'integer'],
            'entries.*.entered_value' => ['required', 'numeric', 'min:0'],
        ]);

        $this->reconciliationService->saveCloseEntries($session, $validated['entries']);

        return response()->json(['message' => 'Closing entries saved.']);
    }

    // ── Comparison preview ────────────────────────────────────────────────────

    public function closePreview(ReconciliationSession $session): JsonResponse
    {
        $this->guardSession($session);

        return response()->json(
            $this->reconciliationService->getClosePreview($session)
        );
    }

    // ── Confirm & close session ───────────────────────────────────────────────

    public function close(Request $request, ReconciliationSession $session): JsonResponse
    {
        $this->guardSession($session);

        $validated = $request->validate([
            'entries'                 => ['required', 'array', 'min:1'],
            'entries.*.type'          => ['required', 'in:account,stock,stock_variation,stock_display,stock_variation_display'],
            'entries.*.reference_id'  => ['required', 'integer'],
            'entries.*.entered_value' => ['required', 'numeric', 'min:0'],
            'adjustment_reason'       => ['nullable', 'string', 'max:2000'],
        ]);

        $result = $this->reconciliationService->closeSession(
            $session,
            $request->user()->id,
            $validated['entries'],
            $validated['adjustment_reason'] ?? null,
        );

        if (is_string($result)) {
            return response()->json(['message' => $result], 422);
        }

        return response()->json(['message' => 'Session closed.', ...$result]);
    }

    // ── History (admin) ───────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 10), 50);

        return response()->json(
            $this->reconciliationService->history(app('tenant')->id, $perPage)
        );
    }

    public function show(ReconciliationSession $session): JsonResponse
    {
        return response()->json(
            $this->reconciliationService->showSession($session, app('tenant')->id)
        );
    }

    // ── Guard helper ──────────────────────────────────────────────────────────

    private function guardSession(ReconciliationSession $session): void
    {
        if ($session->tenant_id !== app('tenant')->id) {
            abort(404);
        }
    }
}
