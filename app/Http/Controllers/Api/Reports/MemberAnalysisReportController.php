<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\MemberAnalysisReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberAnalysisReportController extends Controller
{
    public function __construct(private readonly MemberAnalysisReportService $report) {}

    public function summary(Request $request): JsonResponse
    {
        $filters = $this->validatedFilters($request);

        return response()->json($this->report->summary(app('tenant')->id, $filters));
    }

    public function members(Request $request): JsonResponse
    {
        $filters = $this->validatedFilters($request);
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        return response()->json($this->report->members(app('tenant')->id, $filters, $perPage));
    }

    public function export(Request $request): StreamedResponse
    {
        return $this->report->export(app('tenant')->id, $this->validatedFilters($request));
    }

    public function filterOptions(): JsonResponse
    {
        return response()->json($this->report->filterOptions(app('tenant')->id));
    }

    public function updateMemberStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'member_ids' => ['required', 'array', 'min:1', 'max:500'],
            'member_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        return response()->json($this->report->updateMemberStatus(
            app('tenant')->id,
            $validated['member_ids'],
            $validated['status'],
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request): array
    {
        $validated = $this->validatedFilterPayload($request);

        $pagination = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', 'max:80'],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);

        return array_merge($validated, $pagination);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilterPayload(Request $request): array
    {
        $input = $request->all();

        if (isset($input['filter_rules']) && is_string($input['filter_rules'])) {
            $decoded = json_decode($input['filter_rules'], true);
            $input['filter_rules'] = is_array($decoded) ? $decoded : [];
        }

        return validator($input, [
            'search' => ['nullable', 'string', 'max:255'],
            'member_status' => ['nullable', 'in:active,inactive,temp,verified,unverified'],
            'outstanding_only' => ['nullable', 'boolean'],
            'payment_missed_only' => ['nullable', 'boolean'],
            'inactive_only' => ['nullable', 'boolean'],
            'paid_not_attending_only' => ['nullable', 'boolean'],
            'attending_with_expired_payment_only' => ['nullable', 'boolean'],
            'regular_only' => ['nullable', 'boolean'],
            'new_member_only' => ['nullable', 'boolean'],
            'filter_rules' => ['nullable', 'array', 'max:20'],
            'filter_rules.*.field' => ['required_with:filter_rules', 'string', 'max:80'],
            'filter_rules.*.operator' => ['nullable', 'string', 'max:20'],
            'filter_rules.*.value' => ['nullable'],
        ])->validate();
    }
}
