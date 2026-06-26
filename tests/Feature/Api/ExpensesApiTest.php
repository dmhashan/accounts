<?php

namespace Tests\Feature\Api;

use App\Models\Expense;
use App\Models\ExpenseDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

    private function createExpense(int $accountId): Expense
    {
        return Expense::create([
            'company_account_id' => $accountId,
            'category' => 'Office',
            'amount' => 50,
            'expense_date' => today(),
        ]);
    }
}
