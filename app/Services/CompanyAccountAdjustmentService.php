<?php

namespace App\Services;

use App\Jobs\SendAccountAdjustmentNotificationJob;
use App\Models\CompanyAccount;
use App\Models\CompanyAccountAdjustment;
use App\Models\CompanyAccountTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompanyAccountAdjustmentService
{
    public function __construct(
        private readonly TenantMailService $tenantMail,
    ) {}

    public function adjustments(int $tenantId, int $perPage): array
    {
        $adjustments = CompanyAccountAdjustment::query()
            ->with(['account:id,name', 'creator:id,name'])
            ->orderBy('adjustment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => collect($adjustments->items())->map(fn (CompanyAccountAdjustment $adj) => $this->serialize($adj)),
            'meta' => [
                'current_page' => $adjustments->currentPage(),
                'last_page' => $adjustments->lastPage(),
                'per_page' => $adjustments->perPage(),
                'total' => $adjustments->total(),
            ],
        ];
    }

    public function showAdjustment(CompanyAccountAdjustment $adjustment, int $tenantId): array
    {
        $adjustment->load(['account:id,name', 'creator:id,name']);

        return $this->serialize($adjustment);
    }

    public function storeAdjustment(int $tenantId, array $validated, int $userId): CompanyAccountAdjustment
    {
        return DB::transaction(function () use ($tenantId, $validated, $userId) {
            $this->ensureAccountExists($validated['company_account_id']);

            $adjustment = CompanyAccountAdjustment::create([
                'company_account_id' => $validated['company_account_id'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'reason' => trim($validated['reason']),
                'adjustment_date' => $validated['adjustment_date'],
                'created_by' => $userId,
            ]);

            $this->syncTransaction($adjustment);
            $this->notifyAdmins($tenantId, 'created', $adjustment);

            return $adjustment;
        });
    }

    public function updateAdjustment(CompanyAccountAdjustment $adjustment, int $tenantId, array $validated, int $userId): void
    {
        DB::transaction(function () use ($adjustment, $tenantId, $validated) {
            $locked = CompanyAccountAdjustment::query()
                ->lockForUpdate()
                ->find($adjustment->id);

            if (!$locked) {
                abort(404);
            }

            $this->ensureAccountExists($validated['company_account_id']);

            $locked->update([
                'company_account_id' => $validated['company_account_id'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'reason' => trim($validated['reason']),
                'adjustment_date' => $validated['adjustment_date'],
            ]);

            $this->syncTransaction($locked);
            $this->notifyAdmins($tenantId, 'updated', $locked);
        });
    }

    public function destroyAdjustment(CompanyAccountAdjustment $adjustment, int $tenantId, int $userId): void
    {
        DB::transaction(function () use ($adjustment, $tenantId) {
            $locked = CompanyAccountAdjustment::query()
                ->lockForUpdate()
                ->find($adjustment->id);

            if (!$locked) {
                abort(404);
            }

            $this->notifyAdmins($tenantId, 'deleted', $locked);

            CompanyAccountTransaction::where('model_name', 'adjustment')
                ->where('reference_id', $locked->id)
                ->delete();

            $locked->delete();
        });
    }

    private function ensureAccountExists(int $accountId): void
    {
        $exists = CompanyAccount::query()->where('id', $accountId)->exists();

        if (!$exists) {
            abort(422, 'Invalid account selection.');
        }
    }

    private function syncTransaction(CompanyAccountAdjustment $adjustment): void
    {
        $amount = (float) $adjustment->amount;

        if ($adjustment->type === 'debit') {
            $amount = -$amount;
        }

        CompanyAccountTransaction::updateOrCreate(
            [
                'model_name' => 'adjustment',
                'reference_id' => $adjustment->id,
            ],
            [
                'company_account_id' => $adjustment->company_account_id,
                'type' => 'adjustment',
                'amount' => $amount,
                'transaction_date' => $adjustment->adjustment_date->toDateString(),
                'reference_number' => 'ADJ-' . $adjustment->id,
                'notes' => 'Adjustment: ' . $adjustment->reason,
            ],
        );
    }

    private function notifyAdmins(int $tenantId, string $action, CompanyAccountAdjustment $adjustment): void
    {
        $operator = User::find($adjustment->created_by);

        $details = [
            'account_name' => $adjustment->account?->name ?? 'Unknown',
            'type' => $adjustment->type,
            'amount' => (float) $adjustment->amount,
            'reason' => $adjustment->reason,
            'date' => $adjustment->adjustment_date->toDateString(),
            'operator_name' => $operator?->name ?? 'System',
        ];

        SendAccountAdjustmentNotificationJob::dispatch($tenantId, $action, $details);
    }

    private function serialize(CompanyAccountAdjustment $adj): array
    {
        return [
            'id' => $adj->id,
            'company_account_id' => $adj->company_account_id,
            'account_name' => $adj->account?->name,
            'type' => $adj->type,
            'amount' => round((float) $adj->amount, 2),
            'reason' => $adj->reason,
            'adjustment_date' => $adj->adjustment_date?->toDateString(),
            'created_by' => $adj->created_by,
            'creator_name' => $adj->creator?->name,
            'created_at' => optional($adj->created_at)->format('Y-m-d H:i'),
        ];
    }
}
