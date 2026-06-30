<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberBodyMeasurement;
use App\Services\MemberBodyMeasurementService;
use App\Services\MemberService;
use App\Services\TenantConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberBodyMeasurementApiController extends Controller
{
    public function __construct(
        private readonly MemberBodyMeasurementService $measurements,
        private readonly MemberService $memberService,
        private readonly TenantConfigurationService $configuration,
    ) {}

    public function index(Request $request, Member $member): JsonResponse
    {
        $tenant = app('tenant');
        $this->memberService->ensureTenantMember($member, $tenant->id);

        $perPage = min((int) $request->integer('per_page', 15), 50);

        return response()->json($this->measurements->index($member, $tenant->id, $perPage));
    }

    public function store(Request $request, Member $member): JsonResponse
    {
        $tenant = app('tenant');
        $this->memberService->ensureTenantMember($member, $tenant->id);

        $record = $this->measurements->store(
            $member,
            $tenant->id,
            $this->validatedPayload($request),
            $request->user()?->id,
        );

        return response()->json([
            'message' => 'Body measurement saved successfully.',
            'data' => $this->measurements->serialize(
                $record,
                $this->configuration->bodyMeasurementFields($tenant->id, true),
            ),
        ], 201);
    }

    public function update(Request $request, Member $member, MemberBodyMeasurement $bodyMeasurement): JsonResponse
    {
        $tenant = app('tenant');
        $this->memberService->ensureTenantMember($member, $tenant->id);
        $this->ensureMemberMeasurement($member, $bodyMeasurement);

        $record = $this->measurements->update($bodyMeasurement, $tenant->id, $this->validatedPayload($request));

        return response()->json([
            'message' => 'Body measurement updated successfully.',
            'data' => $this->measurements->serialize(
                $record,
                $this->configuration->bodyMeasurementFields($tenant->id, true),
            ),
        ]);
    }

    public function destroy(Member $member, MemberBodyMeasurement $bodyMeasurement): JsonResponse
    {
        $tenant = app('tenant');
        $this->memberService->ensureTenantMember($member, $tenant->id);
        $this->ensureMemberMeasurement($member, $bodyMeasurement);

        $bodyMeasurement->delete();

        return response()->json([
            'message' => 'Body measurement deleted successfully.',
        ]);
    }

    private function validatedPayload(Request $request): array
    {
        return $request->validate([
            'weight' => ['required', 'numeric', 'min:0.01', 'max:1000'],
            'height' => ['required', 'numeric', 'min:0.01', 'max:300'],
            'measurement_date' => ['required', 'date', 'before_or_equal:today'],
            'measurements' => ['nullable', 'array'],
            'measurements.*' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function ensureMemberMeasurement(Member $member, MemberBodyMeasurement $bodyMeasurement): void
    {
        if ((int) $bodyMeasurement->member_id !== (int) $member->id) {
            abort(404);
        }
    }
}
