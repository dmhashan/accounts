<?php

namespace Tests\Feature\Api;

use App\Models\Expense;
use App\Models\ExpenseDocument;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExpensesApiTest extends ApiRouteTestCase
{
    public function testExpenseCrudDocumentsAndAccountTransactionStayInSync(): void
    {
        $disk = (string) config('filesystems.media_disk', 'public');
        Storage::fake($disk);
        $this->actingAsUser(['accounts.manage']);
        $account = $this->createCompanyAccount(['opening_balance' => 500]);

        $expenseId = (int) $this->postJson('/api/accounts/expenses', [
            'company_account_id' => $account->id,
            'category' => '  Utilities  ',
            'amount' => 125.50,
            'expense_date' => '2026-06-09',
            'reference_number' => '  EXP-100  ',
            'notes' => '  Electricity bill  ',
            'documents' => [
                UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'),
            ],
        ])->assertCreated()
            ->assertJsonPath('message', 'Expense recorded successfully.')
            ->json('data.id');

        $expense = Expense::findOrFail($expenseId);
        $document = ExpenseDocument::where('expense_id', $expenseId)->sole();
        $this->assertSame('Utilities', $expense->category);
        $this->assertStringContainsString($this->tenant->tenant_uuid, $document->path);
        Storage::disk($disk)->assertExists($document->path);
        $this->assertDatabaseHas('company_account_transactions', [
            'tenant_id' => $this->tenant->id,
            'company_account_id' => $account->id,
            'model_name' => 'expense',
            'reference_id' => $expenseId,
            'amount' => -125.50,
        ]);

        $this->getJson('/api/accounts/expenses')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.category', 'Utilities')
            ->assertJsonPath('data.0.documents_count', 1);
        $this->getJson('/api/accounts/expenses/' . $expenseId)
            ->assertOk()
            ->assertJsonPath('data.documents.0.id', $document->id);
        $this->getJson('/api/accounts/transactions')
            ->assertOk()
            ->assertJsonPath('data.0.source_reference', 'Utilities');
        $this->getJson('/api/accounts/expenses/' . $expenseId . '/documents/' . $document->id . '/url')
            ->assertOk()
            ->assertJsonStructure(['url']);

        $this->deleteJson('/api/accounts/expenses/' . $expenseId . '/documents/' . $document->id)
            ->assertOk();
        Storage::disk($disk)->assertMissing($document->path);

        $this->putJson('/api/accounts/expenses/' . $expenseId, [
            'company_account_id' => $account->id,
            'category' => 'Maintenance',
            'amount' => 75,
            'expense_date' => '2026-06-08',
            'documents' => [
                UploadedFile::fake()->create('maintenance.txt', 2, 'text/plain'),
            ],
        ])->assertOk()
            ->assertJsonPath('message', 'Expense updated successfully.');

        $replacementDocument = ExpenseDocument::where('expense_id', $expenseId)->sole();
        Storage::disk($disk)->assertExists($replacementDocument->path);
        $this->assertDatabaseHas('company_account_transactions', [
            'model_name' => 'expense',
            'reference_id' => $expenseId,
            'amount' => -75,
        ]);

        $this->deleteJson('/api/accounts/expenses/' . $expenseId)
            ->assertOk()
            ->assertJsonPath('message', 'Expense deleted successfully.');

        Storage::disk($disk)->assertMissing($replacementDocument->path);
        $this->assertDatabaseMissing('expenses', ['id' => $expenseId]);
        $this->assertDatabaseMissing('company_account_transactions', [
            'model_name' => 'expense',
            'reference_id' => $expenseId,
        ]);
    }

    public function testExpensesRejectCrossTenantAccountsRecordsAndDocuments(): void
    {
        $this->actingAsUser(['accounts.manage']);
        $account = $this->createCompanyAccount();
        $otherTenant = Tenant::create([
            'name' => 'Other Gym',
            'domain' => 'other-expenses',
            'tenant_uuid' => Str::uuid()->toString(),
        ]);
        $otherAccount = $this->createCompanyAccount(['tenant_id' => $otherTenant->id]);
        $expense = $this->createExpense($account->id, $this->tenant->id);
        $otherExpense = $this->createExpense($otherAccount->id, $otherTenant->id);
        $otherDocument = ExpenseDocument::create([
            'tenant_id' => $otherTenant->id,
            'expense_id' => $otherExpense->id,
            'path' => 'other/receipt.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10,
            'original_filename' => 'receipt.pdf',
        ]);

        $this->postJson('/api/accounts/expenses', [
            'company_account_id' => $otherAccount->id,
            'category' => 'Private',
            'amount' => 10,
            'expense_date' => '2026-06-09',
        ])->assertUnprocessable();

        $this->getJson('/api/accounts/expenses/' . $otherExpense->id)->assertNotFound();
        $this->putJson('/api/accounts/expenses/' . $otherExpense->id, [
            'company_account_id' => $account->id,
            'category' => 'Changed',
            'amount' => 10,
            'expense_date' => '2026-06-09',
        ])->assertNotFound();
        $this->deleteJson('/api/accounts/expenses/' . $otherExpense->id)->assertNotFound();
        $this->getJson('/api/accounts/expenses/' . $expense->id . '/documents/' . $otherDocument->id . '/url')
            ->assertNotFound();
        $this->deleteJson('/api/accounts/expenses/' . $expense->id . '/documents/' . $otherDocument->id)
            ->assertNotFound();
    }

    private function createExpense(int $accountId, int $tenantId): Expense
    {
        return Expense::create([
            'tenant_id' => $tenantId,
            'company_account_id' => $accountId,
            'category' => 'Office',
            'amount' => 50,
            'expense_date' => today(),
        ]);
    }
}
