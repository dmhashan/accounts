<?php

namespace Tests\Feature\Api;

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
            'company_account_id' => $account->id,
            'model_name' => 'wallet_topup',
            'reference_id' => $topupId,
            'type' => 'wallet_topup',
            'amount' => 250,
        ]);
        $this->assertDatabaseHas('audit_logs', [
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
}
