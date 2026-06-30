<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberBodyMeasurement;

class MemberBodyMeasurementService
{
    public function __construct(private readonly TenantConfigurationService $configuration) {}

    public function index(Member $member, int $tenantId, int $perPage): array
    {
        $fields = $this->configuration->bodyMeasurementFields($tenantId, true);

        $records = MemberBodyMeasurement::query()
            ->where('member_id', $member->id)
            ->orderByDesc('measurement_date')
            ->orderByDesc('id')
            ->paginate($perPage);

        $latestRecords = MemberBodyMeasurement::query()
            ->where('member_id', $member->id)
            ->orderByDesc('measurement_date')
            ->orderByDesc('id')
            ->limit(2)
            ->get();

        return [
            'fields' => $fields,
            'data' => collect($records->items())->map(fn (MemberBodyMeasurement $record): array => $this->serialize($record, $fields))->values(),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
            'latest' => $latestRecords->get(0) ? $this->serialize($latestRecords->get(0), $fields) : null,
            'previous' => $latestRecords->get(1) ? $this->serialize($latestRecords->get(1), $fields) : null,
        ];
    }

    public function store(Member $member, int $tenantId, array $validated, ?int $createdBy): MemberBodyMeasurement
    {
        $fields = $this->configuration->bodyMeasurementFields($tenantId, true);

        return MemberBodyMeasurement::create([
            'member_id' => $member->id,
            'weight' => (float) $validated['weight'],
            'height' => (float) $validated['height'],
            'measurement_date' => $validated['measurement_date'],
            'measurements' => $this->sanitizeMeasurementValues($validated['measurements'] ?? [], $fields),
            'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            'created_by' => $createdBy,
        ]);
    }

    public function update(MemberBodyMeasurement $record, int $tenantId, array $validated): MemberBodyMeasurement
    {
        $fields = $this->configuration->bodyMeasurementFields($tenantId, true);

        $record->update([
            'weight' => (float) $validated['weight'],
            'height' => (float) $validated['height'],
            'measurement_date' => $validated['measurement_date'],
            'measurements' => $this->sanitizeMeasurementValues($validated['measurements'] ?? [], $fields),
            'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
        ]);

        return $record->refresh();
    }

    public function serialize(MemberBodyMeasurement $record, array $fields): array
    {
        $values = is_array($record->measurements) ? $record->measurements : [];

        return [
            'id' => $record->id,
            'weight' => round((float) $record->weight, 2),
            'height' => round((float) $record->height, 2),
            'measurement_date' => optional($record->measurement_date)->toDateString(),
            'measurements' => $values,
            'measurement_fields' => collect($fields)->map(fn (array $field): array => [
                ...$field,
                'value' => array_key_exists($field['key'], $values) ? round((float) $values[$field['key']], 2) : null,
            ])->values(),
            'notes' => $record->notes,
            'created_at' => optional($record->created_at)->toISOString(),
        ];
    }

    /**
     * @param  array<int|string, mixed>  $values
     * @param  array<int, array{key: string}>  $fields
     * @return array<string, float>
     */
    private function sanitizeMeasurementValues(array $values, array $fields): array
    {
        $clean = [];

        foreach ($fields as $field) {
            $key = $field['key'];

            if (!array_key_exists($key, $values) || $values[$key] === null || $values[$key] === '') {
                continue;
            }

            $clean[$key] = round((float) $values[$key], 2);
        }

        return $clean;
    }
}
