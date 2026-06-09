<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use Illuminate\Support\Str;

class WalletApiTest extends ApiRouteTestCase
{
    public function testWalletTopupCreditsMemberAccountAndHistory(): void
    {
        $user = $this->actingAsUser(['payments.manage']);
        $member = $this->createMember(attributes: ['current_balance' => 100]);
        $account = $this->createCompanyAccount(['opening_balance' => 500]);

        $topupId = (int) $this->postJson('/api/members/' . $member->id . '/wallet/topup', [
            'company_account_id' => $account->id,
            'amount' => 250,
            'topup_date' => now()->toDateString(),
            'reference_number' => 'TOPUP-001',
            'notes' => 'Reception cash',
        ])->assertCreated()
            ->assertJsonPath('current_balance', 350)
            ->json('topup.id');

        $this->assertDatabaseHas('company_account_transactions', [
            'tenant_id' => $this->tenant->id,
            'company_account_id' => $account->id,
            'model_name' => 'wallet_topup',
            'reference_id' => $topupId,
            'type' => 'wallet_topup',
            'amount' => 250,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'action' => 'wallet_topup',
            'auditable_id' => $topupId,
        ]);

        $this->getJson('/api/wallet-topups/' . $topupId)
            ->assertOk()
            ->assertJsonPath('member.id', $member->id);

        $this->getJson('/api/members/' . $member->id . '/wallet/topup-history')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $topupId);

        $this->getJson('/api/members/' . $member->id . '/wallet/transactions')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.direction', 'credit');

        $this->getJson('/api/wallet/meta')
            ->assertOk()
            ->assertJsonPath('accounts.0.current_balance', 750);
    }

    public function testWalletTopupRejectsCrossTenantMemberAndAccount(): void
    {
        $this->actingAsUser(['payments.manage']);
        $member = $this->createMember();
        $otherTenant = Tenant::create([
            'name' => 'Other Gym',
            'domain' => 'other-wallet',
            'tenant_uuid' => Str::uuid()->toString(),
        ]);
        $otherMember = $this->createMember(attributes: ['tenant_id' => $otherTenant->id]);
        $otherAccount = $this->createCompanyAccount(['tenant_id' => $otherTenant->id]);

        $this->postJson('/api/members/' . $otherMember->id . '/wallet/topup', [
            'company_account_id' => $otherAccount->id,
            'amount' => 50,
            'topup_date' => now()->toDateString(),
        ])->assertNotFound();

        $this->postJson('/api/members/' . $member->id . '/wallet/topup', [
            'company_account_id' => $otherAccount->id,
            'amount' => 50,
            'topup_date' => now()->toDateString(),
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Invalid company account selection.');

        $this->assertDatabaseMissing('wallet_topups', ['company_account_id' => $otherAccount->id]);
    }
}
