<?php

namespace Tests\Feature\Api;

class CompanyAccountsApiTest extends ApiRouteTestCase
{
    public function testAccountsMetaAndAccountRoutesCoverListShowCreateUpdateAndDelete(): void
    {
        $this->actingAsUser(['accounts.manage']);

        $existingAccount = $this->createCompanyAccount([
            'name' => 'Main Bank',
            'opening_balance' => 500,
        ]);

        $this->getJson('/api/accounts/meta')
            ->assertOk()
            ->assertJsonStructure(['accounts']);

        $this->getJson('/api/accounts')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);

        $this->getJson('/api/accounts/' . $existingAccount->id)
            ->assertOk()
            ->assertJsonPath('data.id', $existingAccount->id)
            ->assertJsonPath('data.current_balance', 500);

        $storeResponse = $this->postJson('/api/accounts', [
            'name' => 'Cash Drawer',
            'opening_balance' => 150.50,
            'description' => 'Front desk cash drawer',
        ]);

        $storeResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Account created successfully.');

        $accountId = (int) $storeResponse->json('data.id');

        $this->putJson('/api/accounts/' . $accountId, [
            'name' => 'Cash Drawer Updated',
            'opening_balance' => 225.75,
            'description' => 'Updated cash drawer',
        ])->assertOk()->assertJsonPath('message', 'Account updated successfully.');

        $this->getJson('/api/accounts/' . $accountId)
            ->assertOk()
            ->assertJsonPath('data.current_balance', 225.75);

        $this->deleteJson('/api/accounts/' . $accountId)
            ->assertOk()
            ->assertJsonPath('message', 'Account deleted successfully.');
    }

    public function testTransferRoutesCoverListShowCreateUpdateDeleteAndBalanceChanges(): void
    {
        $this->actingAsUser(['accounts.manage']);

        $sourceAccount = $this->createCompanyAccount([
            'name' => 'Primary Bank',
            'opening_balance' => 500,
        ]);
        $destinationAccount = $this->createCompanyAccount([
            'name' => 'Cash Box',
            'opening_balance' => 100,
        ]);

        $this->getJson('/api/accounts/transfers')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);

        $storeResponse = $this->postJson('/api/accounts/transfers', [
            'source_account_id' => $sourceAccount->id,
            'destination_account_id' => $destinationAccount->id,
            'amount' => 150,
            'transfer_date' => now()->toDateString(),
            'reference_number' => 'TRF-100',
            'notes' => 'Initial funding',
        ]);

        $storeResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Transfer created successfully.');

        $transferId = (int) $storeResponse->json('data.id');

        $this->getJson('/api/accounts/transfers/' . $transferId)
            ->assertOk()
            ->assertJsonPath('data.id', $transferId)
            ->assertJsonPath('data.amount', 150);

        $this->getJson('/api/accounts/' . $sourceAccount->id)
            ->assertOk()
            ->assertJsonPath('data.current_balance', 350);

        $this->getJson('/api/accounts/' . $destinationAccount->id)
            ->assertOk()
            ->assertJsonPath('data.current_balance', 250);

        $this->putJson('/api/accounts/transfers/' . $transferId, [
            'source_account_id' => $sourceAccount->id,
            'destination_account_id' => $destinationAccount->id,
            'amount' => 90,
            'transfer_date' => now()->toDateString(),
            'reference_number' => 'TRF-101',
            'notes' => 'Adjusted transfer',
        ])->assertOk()->assertJsonPath('message', 'Transfer updated successfully.');

        $this->getJson('/api/accounts/' . $sourceAccount->id)
            ->assertOk()
            ->assertJsonPath('data.current_balance', 410);

        $this->getJson('/api/accounts/' . $destinationAccount->id)
            ->assertOk()
            ->assertJsonPath('data.current_balance', 190);

        $this->deleteJson('/api/accounts/transfers/' . $transferId)
            ->assertOk()
            ->assertJsonPath('message', 'Transfer deleted successfully.');

        $this->getJson('/api/accounts/' . $sourceAccount->id)
            ->assertOk()
            ->assertJsonPath('data.current_balance', 500);

        $this->getJson('/api/accounts/' . $destinationAccount->id)
            ->assertOk()
            ->assertJsonPath('data.current_balance', 100);
    }

    public function testTransferCreationRequiresSufficientBalance(): void
    {
        $this->actingAsUser(['accounts.manage']);

        $sourceAccount = $this->createCompanyAccount([
            'opening_balance' => 100,
        ]);
        $destinationAccount = $this->createCompanyAccount([
            'opening_balance' => 0,
        ]);

        $this->postJson('/api/accounts/transfers', [
            'source_account_id' => $sourceAccount->id,
            'destination_account_id' => $destinationAccount->id,
            'amount' => 150,
            'transfer_date' => now()->toDateString(),
        ])->assertStatus(422)->assertJsonPath('message', 'Insufficient balance in source account.');
    }

    public function testAccountCannotBeDeletedWhenTransferHistoryExists(): void
    {
        $this->actingAsUser(['accounts.manage']);

        $sourceAccount = $this->createCompanyAccount(['opening_balance' => 300]);
        $destinationAccount = $this->createCompanyAccount(['opening_balance' => 50]);

        $this->createCompanyAccountTransfer($sourceAccount, $destinationAccount, ['amount' => 75]);

        $this->deleteJson('/api/accounts/' . $sourceAccount->id)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Account cannot be deleted because transaction history exists.');
    }
}
