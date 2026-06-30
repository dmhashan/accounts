<?php

namespace Tests\Feature\Api;

use App\Models\CompanyAccountTransaction;
use App\Models\Employee;
use App\Models\EmployeePaySheetRun;
use App\Models\Expense;

class EmployeePaySheetsApiTest extends ApiRouteTestCase
{
    public function testPaySheetPaymentCreatesLinkedExpenseAndAccountTransaction(): void
    {
        $this->actingAsUser(['employee_pay_sheets.manage', 'accounts.manage']);

        $account = $this->createCompanyAccount(['name' => 'Payroll Cash']);
        $employee = Employee::create([
            'first_name' => 'Nimal',
            'last_name' => 'Perera',
            'name' => 'Nimal Perera',
            'status' => 'active',
            'joined_date' => '2026-01-01',
            'pay_method' => 'daily',
            'daily_rate' => 100,
        ]);

        $this->postJson("/api/employees/{$employee->id}/pay-sheets/generate", [
            'month' => '2026-06',
        ])->assertCreated();

        $run = EmployeePaySheetRun::query()->sole();

        $this->postJson("/api/employee-pay-sheets/{$run->id}/mark-paid", [
            'company_account_id' => $account->id,
            'paid_at' => '2026-07-01',
            'reference_number' => 'PAY-2026-06',
        ])->assertOk()
            ->assertJsonPath('message', 'Employee Pay Sheet paid and expense recorded.');

        $run->refresh();
        $expense = Expense::query()->findOrFail($run->expense_id);

        $this->assertSame('paid', $run->status);
        $this->assertSame($account->id, $run->company_account_id);
        $this->assertSame('Staff Salaries', $expense->category);
        $this->assertSame($account->id, $expense->company_account_id);
        $this->assertSame(3000.00, (float) $expense->amount);
        $this->assertSame('2026-07-01', $expense->expense_date->toDateString());
        $this->assertSame('PAY-2026-06', $expense->reference_number);

        $this->assertDatabaseHas('company_account_transactions', [
            'company_account_id' => $account->id,
            'model_name' => 'expense',
            'reference_id' => $expense->id,
            'amount' => -3000,
        ]);
        $this->assertSame(1, CompanyAccountTransaction::query()->count());

        $this->getJson('/api/accounts/expenses')
            ->assertOk()
            ->assertJsonPath('data.0.id', $expense->id)
            ->assertJsonPath('data.0.category', 'Staff Salaries');

        $this->deleteJson("/api/accounts/expenses/{$expense->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'This expense is linked to a paid employee pay sheet and cannot be edited or deleted.');
    }
}
