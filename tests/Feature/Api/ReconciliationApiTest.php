<?php

namespace Tests\Feature\Api;

use App\Models\CompanyAccountTransaction;
use App\Models\ReconciliationSession;
use App\Models\Tenant;
use Illuminate\Support\Str;

class ReconciliationApiTest extends ApiRouteTestCase
{
    public function testReconciliationSessionCanBeOpenedPreviewedAndClosed(): void
    {
        $user = $this->actingAsUser(['reconciliation.perform']);
        $account = $this->createCompanyAccount(['opening_balance' => 1000]);

        $sessionId = (int) $this->postJson('/api/reconciliation/open', [
            'entries' => [[
                'type' => 'account',
                'reference_id' => $account->id,
                'entered_value' => 1000,
            ]],
        ])->assertCreated()
            ->assertJsonPath('session.status', 'open')
            ->json('session.id');

        CompanyAccountTransaction::create([
            'tenant_id' => $this->tenant->id,
            'company_account_id' => $account->id,
            'model_name' => 'manual-test',
            'reference_id' => 999,
            'type' => 'credit',
            'amount' => 250,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->getJson('/api/reconciliation/sessions/' . $sessionId . '/preview')
            ->assertOk()
            ->assertJsonPath('items.0.opening_value', 1000)
            ->assertJsonPath('items.0.system_delta', 250)
            ->assertJsonPath('items.0.expected_close', 1250)
            ->assertJsonPath('items.0.actual_close', null);

        $this->postJson('/api/reconciliation/sessions/' . $sessionId . '/close')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Please complete all closing values before confirming.');

        $this->postJson('/api/reconciliation/sessions/' . $sessionId . '/save-close', [
            'entries' => [[
                'type' => 'account',
                'reference_id' => $account->id,
                'entered_value' => 1240,
            ]],
        ])->assertOk();

        $this->postJson('/api/reconciliation/sessions/' . $sessionId . '/close', [
            'adjustment_reason' => 'Cash count is short by 10.',
        ])->assertOk()
            ->assertJsonPath('session.status', 'closed')
            ->assertJsonPath('items.0.difference', -10);

        $this->assertDatabaseHas('reconciliation_sessions', [
            'id' => $sessionId,
            'tenant_id' => $this->tenant->id,
            'status' => 'closed',
            'opened_by' => $user->id,
            'closed_by' => $user->id,
            'adjustment_reason' => 'Cash count is short by 10.',
        ]);

        $this->postJson('/api/reconciliation/sessions/' . $sessionId . '/close')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This session is already closed.');
    }

    public function testOnlyOneReconciliationSessionCanExistPerTenantPerDay(): void
    {
        $this->actingAsUser(['reconciliation.perform']);
        $account = $this->createCompanyAccount();
        $payload = [
            'entries' => [[
                'type' => 'account',
                'reference_id' => $account->id,
                'entered_value' => 0,
            ]],
        ];

        $this->postJson('/api/reconciliation/open', $payload)->assertCreated();
        $this->postJson('/api/reconciliation/open', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'A reconciliation session for today already exists.');

        $this->getJson('/api/reconciliation/today')
            ->assertOk()
            ->assertJsonPath('session.status', 'open');
    }

    public function testReconciliationConfigurationAndHistoryArePermissionAndTenantScoped(): void
    {
        $role = $this->createRole('cashier');
        $this->actingAsUser(['reconciliation.manage'], role: $role);
        $account = $this->createCompanyAccount();

        $this->postJson('/api/reconciliation/config', [
            'role_id' => $role->id,
            'items' => [[
                'type' => 'account',
                'reference_id' => $account->id,
                'is_active' => true,
            ]],
        ])->assertOk();

        $this->assertDatabaseHas('reconciliation_configs', [
            'tenant_id' => $this->tenant->id,
            'role_id' => $role->id,
            'reference_id' => $account->id,
            'is_active' => true,
        ]);

        $this->getJson('/api/reconciliation/config')
            ->assertOk()
            ->assertJsonPath('accounts.0.id', $account->id);

        $otherTenant = Tenant::create([
            'name' => 'Other Gym',
            'domain' => 'other-reconciliation',
            'tenant_uuid' => Str::uuid()->toString(),
        ]);
        $otherSession = ReconciliationSession::create([
            'tenant_id' => $otherTenant->id,
            'date' => now()->subDay()->toDateString(),
            'status' => 'open',
            'opened_by' => auth()->id(),
        ]);

        $this->getJson('/api/reconciliation/sessions/' . $otherSession->id)->assertNotFound();
    }
}
