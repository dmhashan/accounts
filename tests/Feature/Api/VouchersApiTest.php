<?php

namespace Tests\Feature\Api;

use App\Models\Voucher;
use Illuminate\Support\Str;

class VouchersApiTest extends ApiRouteTestCase
{
    public function testVoucherCrudIsTenantScopedAndAudited(): void
    {
        $this->actingAsUser(['vouchers.manage']);

        $response = $this->postJson('/api/vouchers', [
            'name' => 'Welcome Credit',
            'amount' => 2500,
            'status' => 'active',
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addMonth()->toDateString(),
        ])->assertCreated()
            ->assertJsonPath('voucher.name', 'Welcome Credit')
            ->assertJsonPath('voucher.amount', 2500);

        $voucherId = (int) $response->json('voucher.id');

        $this->getJson('/api/vouchers?search=Welcome')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $voucherId);

        $this->getJson('/api/vouchers/' . $voucherId)
            ->assertOk()
            ->assertJsonPath('id', $voucherId);

        $this->putJson('/api/vouchers/' . $voucherId, [
            'name' => 'Updated Credit',
            'amount' => 3000,
            'status' => 'inactive',
        ])->assertOk()
            ->assertJsonPath('voucher.name', 'Updated Credit');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'voucher_create',
            'auditable_id' => $voucherId,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'voucher_update',
            'auditable_id' => $voucherId,
        ]);

        $this->deleteJson('/api/vouchers/' . $voucherId)
            ->assertOk()
            ->assertJsonPath('message', 'Voucher deleted.');

        $this->assertDatabaseMissing('vouchers', ['id' => $voucherId]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'voucher_delete',
            'auditable_id' => $voucherId,
        ]);
    }

    public function testRedeemingAVoucherCreditsMemberOnceAndLocksTheVoucher(): void
    {
        $user = $this->actingAsUser(['vouchers.manage', 'payments.manage']);
        $member = $this->createMember(attributes: ['current_balance' => 100]);
        $voucher = $this->createVoucher([
            'amount' => 750,
            'created_by' => $user->id,
        ]);

        $this->postJson('/api/members/' . $member->id . '/wallet/redeem-voucher', [
            'uuid' => $voucher->uuid,
            'notes' => 'Issued at reception',
        ])->assertCreated()
            ->assertJsonPath('current_balance', 850)
            ->assertJsonPath('redemption.voucher.id', $voucher->id);

        $this->assertDatabaseHas('voucher_redemptions', [
            'voucher_id' => $voucher->id,
            'member_id' => $member->id,
            'redeemed_by' => $user->id,
            'notes' => 'Issued at reception',
        ]);
        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'status' => 'redeemed',
        ]);
        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'current_balance' => 850,
        ]);

        $this->getJson('/api/members/' . $member->id . '/wallet/voucher-redemptions')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.voucher.id', $voucher->id);

        $this->postJson('/api/members/' . $member->id . '/wallet/redeem-voucher', [
            'uuid' => $voucher->uuid,
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'This voucher has already been redeemed.');

        $this->putJson('/api/vouchers/' . $voucher->id, [
            'name' => 'Changed',
            'amount' => 1,
            'status' => 'active',
        ])->assertUnprocessable();

        $this->deleteJson('/api/vouchers/' . $voucher->id)->assertUnprocessable();
        $this->assertDatabaseHas('members', ['id' => $member->id, 'current_balance' => 850]);
    }

    private function createVoucher(array $attributes = []): Voucher
    {
        return Voucher::create(array_merge([
            'name' => 'Voucher ' . Str::random(6),
            'uuid' => Str::uuid()->toString(),
            'amount' => 500,
            'status' => 'active',
        ], $attributes));
    }
}
