<?php

namespace Tests\Feature\Api;

use App\Jobs\SendAccountAdjustmentNotificationJob;
use App\Models\CompanyAccountAdjustment;
use Illuminate\Support\Facades\Queue;

class CompanyAccountAdjustmentApiTest extends ApiRouteTestCase
{
    public function testIndexReturnsPaginatedAdjustments(): void
    {
        $this->actingAsUser(['accounts.adjust', 'accounts.manage']);
        $account = $this->createCompanyAccount();

        CompanyAccountAdjustment::create([
            'company_account_id' => $account->id,
            'type' => 'credit',
            'amount' => 150.00,
            'reason' => 'Test credit',
            'adjustment_date' => now()->toDateString(),
        ]);

        CompanyAccountAdjustment::create([
            'company_account_id' => $account->id,
            'type' => 'debit',
            'amount' => 50.00,
            'reason' => 'Test debit',
            'adjustment_date' => now()->toDateString(),
        ]);

        $this->getJson('/api/accounts/adjustments')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    }

    public function testStoreCreatesAdjustmentAndTransactionAndSendsEmail(): void
    {
        Queue::fake();

        $user = $this->actingAsUser(['accounts.adjust', 'accounts.manage']);
        $account = $this->createCompanyAccount(['opening_balance' => 1000]);

        // Create an admin user to verify email dispatch
        $adminRole = $this->createRole('admin');
        $admin = $this->createUser(attributes: ['email' => 'admin@test.com', 'role_id' => $adminRole->id]);

        $response = $this->postJson('/api/accounts/adjustments', [
            'company_account_id' => $account->id,
            'type' => 'debit',
            'amount' => 200.00,
            'reason' => 'Monthly fee correction',
            'adjustment_date' => now()->toDateString(),
        ])->assertCreated();

        $adjustmentId = $response->json('data.id');

        $this->assertDatabaseHas('company_account_adjustments', [
            'id' => $adjustmentId,
            'company_account_id' => $account->id,
            'type' => 'debit',
            'amount' => 200.00,
            'reason' => 'Monthly fee correction',
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('company_account_transactions', [
            'company_account_id' => $account->id,
            'model_name' => 'adjustment',
            'reference_id' => $adjustmentId,
            'type' => 'adjustment',
            'amount' => -200.00,
        ]);

        // Assert balance updates
        $this->getJson('/api/accounts/' . $account->id)
            ->assertOk()
            ->assertJsonPath('data.current_balance', 800);

        Queue::assertPushed(SendAccountAdjustmentNotificationJob::class, function ($job) {
            return $job->action === 'created';
        });
    }

    public function testUpdateModifiesAdjustmentAndTransactionAndSendsEmail(): void
    {
        Queue::fake();

        $user = $this->actingAsUser(['accounts.adjust', 'accounts.manage']);
        $account = $this->createCompanyAccount(['opening_balance' => 1000]);

        $adminRole = $this->createRole('admin');
        $admin = $this->createUser(attributes: ['email' => 'admin@test.com', 'role_id' => $adminRole->id]);

        $adjustment = CompanyAccountAdjustment::create([
            'company_account_id' => $account->id,
            'type' => 'credit',
            'amount' => 500.00,
            'reason' => 'Initial mistake',
            'adjustment_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        // Sync initial transaction to DB
        $adjustmentAmount = 500.00;
        \App\Models\CompanyAccountTransaction::create([
            'company_account_id' => $account->id,
            'model_name' => 'adjustment',
            'reference_id' => $adjustment->id,
            'type' => 'adjustment',
            'amount' => $adjustmentAmount,
            'transaction_date' => $adjustment->adjustment_date->toDateString(),
            'reference_number' => 'ADJ-' . $adjustment->id,
            'notes' => 'Adjustment: ' . $adjustment->reason,
        ]);

        $this->putJson('/api/accounts/adjustments/' . $adjustment->id, [
            'company_account_id' => $account->id,
            'type' => 'debit',
            'amount' => 300.00,
            'reason' => 'Corrected mistake to debit',
            'adjustment_date' => now()->toDateString(),
        ])->assertOk();

        $this->assertDatabaseHas('company_account_adjustments', [
            'id' => $adjustment->id,
            'type' => 'debit',
            'amount' => 300.00,
            'reason' => 'Corrected mistake to debit',
        ]);

        $this->assertDatabaseHas('company_account_transactions', [
            'model_name' => 'adjustment',
            'reference_id' => $adjustment->id,
            'amount' => -300.00,
        ]);

        // Assert balance updates (1000 - 300)
        $this->getJson('/api/accounts/' . $account->id)
            ->assertOk()
            ->assertJsonPath('data.current_balance', 700);

        Queue::assertPushed(SendAccountAdjustmentNotificationJob::class, function ($job) {
            return $job->action === 'updated';
        });
    }

    public function testDestroyDeletesAdjustmentAndTransactionAndSendsEmail(): void
    {
        Queue::fake();

        $user = $this->actingAsUser(['accounts.adjust', 'accounts.manage']);
        $account = $this->createCompanyAccount(['opening_balance' => 1000]);

        $adminRole = $this->createRole('admin');
        $admin = $this->createUser(attributes: ['email' => 'admin@test.com', 'role_id' => $adminRole->id]);

        $adjustment = CompanyAccountAdjustment::create([
            'company_account_id' => $account->id,
            'type' => 'debit',
            'amount' => 400.00,
            'reason' => 'Refund correction',
            'adjustment_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        \App\Models\CompanyAccountTransaction::create([
            'company_account_id' => $account->id,
            'model_name' => 'adjustment',
            'reference_id' => $adjustment->id,
            'type' => 'adjustment',
            'amount' => -400.00,
            'transaction_date' => $adjustment->adjustment_date->toDateString(),
            'reference_number' => 'ADJ-' . $adjustment->id,
            'notes' => 'Adjustment: ' . $adjustment->reason,
        ]);

        $this->deleteJson('/api/accounts/adjustments/' . $adjustment->id)->assertOk();

        $this->assertDatabaseMissing('company_account_adjustments', [
            'id' => $adjustment->id,
        ]);

        $this->assertDatabaseMissing('company_account_transactions', [
            'model_name' => 'adjustment',
            'reference_id' => $adjustment->id,
        ]);

        // Assert balance goes back to 1000
        $this->getJson('/api/accounts/' . $account->id)
            ->assertOk()
            ->assertJsonPath('data.current_balance', 1000);

        Queue::assertPushed(SendAccountAdjustmentNotificationJob::class, function ($job) {
            return $job->action === 'deleted';
        });
    }
}
