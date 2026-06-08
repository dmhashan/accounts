<?php

namespace App\Services;

use App\Models\CompanyAccount;
use App\Models\CompanyAccountTransaction;
use App\Models\Expense;
use App\Models\ExpenseDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public const MAX_DOCUMENT_SIZE_KB = 10240; // 10 MB

    public function __construct(private readonly MediaStorageService $media) {}

    public function expenses(int $tenantId, int $perPage): array
    {
        $expenses = Expense::query()
            ->where('tenant_id', $tenantId)
            ->with('account:id,name')
            ->withCount('documents')
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => collect($expenses->items())->map(fn (Expense $expense) => $this->serialize($expense)),
            'meta' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
            ],
        ];
    }

    public function showExpense(Expense $expense, int $tenantId): array
    {
        $expense = Expense::query()
            ->where('tenant_id', $tenantId)
            ->with(['account:id,name', 'documents.uploader:id,name'])
            ->find($expense->id);

        if (!$expense) {
            abort(404);
        }

        return $this->serialize($expense);
    }

    public function storeExpense(int $tenantId, array $validated, array $documents = [], ?int $uploadedBy = null): Expense
    {
        return DB::transaction(function () use ($tenantId, $validated, $documents, $uploadedBy) {
            $this->ensureAccountBelongsToTenant((int) $validated['company_account_id'], $tenantId);

            $expense = Expense::create([
                'tenant_id' => $tenantId,
                'company_account_id' => $validated['company_account_id'],
                'category' => trim($validated['category']),
                'amount' => $validated['amount'],
                'expense_date' => $validated['expense_date'],
                'reference_number' => filled($validated['reference_number'] ?? null) ? trim((string) $validated['reference_number']) : null,
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            ]);

            $this->syncTransaction($expense, $tenantId);
            $this->storeDocuments($expense, $tenantId, $documents, $uploadedBy);

            return $expense;
        });
    }

    public function updateExpense(Expense $expense, int $tenantId, array $validated, array $documents = [], ?int $uploadedBy = null): void
    {
        DB::transaction(function () use ($expense, $tenantId, $validated, $documents, $uploadedBy) {
            $lockedExpense = Expense::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->find($expense->id);

            if (!$lockedExpense) {
                abort(404);
            }

            $this->ensureAccountBelongsToTenant((int) $validated['company_account_id'], $tenantId);

            $lockedExpense->update([
                'company_account_id' => $validated['company_account_id'],
                'category' => trim($validated['category']),
                'amount' => $validated['amount'],
                'expense_date' => $validated['expense_date'],
                'reference_number' => filled($validated['reference_number'] ?? null) ? trim((string) $validated['reference_number']) : null,
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            ]);

            $this->syncTransaction($lockedExpense, $tenantId);
            $this->storeDocuments($lockedExpense, $tenantId, $documents, $uploadedBy);
        });
    }

    public function destroyExpense(Expense $expense, int $tenantId): void
    {
        $lockedExpense = Expense::query()
            ->where('tenant_id', $tenantId)
            ->find($expense->id);

        if (!$lockedExpense) {
            abort(404);
        }

        // Delete the associated transaction before deleting the expense
        CompanyAccountTransaction::where('model_name', 'expense')
            ->where('reference_id', $lockedExpense->id)
            ->delete();

        foreach ($lockedExpense->documents as $document) {
            $this->media->delete($document->path);
        }

        $lockedExpense->delete();
    }

    public function documentUrl(Expense $expense, ExpenseDocument $document, int $tenantId): string
    {
        $this->ensureDocumentBelongsToExpense($expense, $document, $tenantId);

        return $this->media->url($document->path);
    }

    public function destroyDocument(Expense $expense, ExpenseDocument $document, int $tenantId): void
    {
        $this->ensureDocumentBelongsToExpense($expense, $document, $tenantId);

        $this->media->delete($document->path);
        $document->delete();
    }

    private function syncTransaction(Expense $expense, int $tenantId): void
    {
        // Expenses are debits: stored as negative amounts so they reduce the account balance
        CompanyAccountTransaction::updateOrCreate(
            [
                'model_name' => 'expense',
                'reference_id' => $expense->id,
            ],
            [
                'tenant_id' => $tenantId,
                'company_account_id' => $expense->company_account_id,
                'type' => 'expense',
                'amount' => -(float) $expense->amount,
                'transaction_date' => $expense->expense_date->toDateString(),
                'reference_number' => $expense->reference_number,
                'notes' => filled($expense->notes) ? $expense->notes : 'Expense: ' . $expense->category,
            ],
        );
    }

    private function ensureAccountBelongsToTenant(int $accountId, int $tenantId): void
    {
        $exists = CompanyAccount::query()
            ->where('id', $accountId)
            ->where('tenant_id', $tenantId)
            ->exists();

        if (!$exists) {
            abort(422, 'Invalid account selection.');
        }
    }

    private function ensureDocumentBelongsToExpense(Expense $expense, ExpenseDocument $document, int $tenantId): void
    {
        if (
            $expense->tenant_id !== $tenantId ||
            $document->tenant_id !== $tenantId ||
            $document->expense_id !== $expense->id
        ) {
            abort(404);
        }
    }

    private function storeDocuments(Expense $expense, int $tenantId, array $documents, ?int $uploadedBy): void
    {
        foreach ($documents as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $path = $this->media->store($file, "expenses/{$expense->id}/documents");

            ExpenseDocument::create([
                'tenant_id' => $tenantId,
                'expense_id' => $expense->id,
                'uploaded_by' => $uploadedBy,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'original_filename' => $file->getClientOriginalName(),
            ]);
        }
    }

    private function serialize(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'company_account_id' => $expense->company_account_id,
            'account_name' => $expense->account?->name,
            'category' => $expense->category,
            'amount' => round((float) $expense->amount, 2),
            'expense_date' => $expense->expense_date?->toDateString(),
            'reference_number' => $expense->reference_number,
            'notes' => $expense->notes,
            'documents_count' => $expense->documents_count ?? $expense->documents->count(),
            'documents' => $expense->relationLoaded('documents')
                ? $expense->documents->map(fn (ExpenseDocument $document) => $this->serializeDocument($document))->values()->all()
                : [],
            'created_at' => optional($expense->created_at)->format('Y-m-d H:i'),
        ];
    }

    public function serializeDocument(ExpenseDocument $document): array
    {
        return [
            'id' => $document->id,
            'mime_type' => $document->mime_type,
            'file_size' => $document->file_size,
            'original_filename' => $document->original_filename,
            'uploaded_by' => $document->uploader ? ['id' => $document->uploader->id, 'name' => $document->uploader->name] : null,
            'created_at' => optional($document->created_at)->format('d M Y, H:i'),
        ];
    }
}
