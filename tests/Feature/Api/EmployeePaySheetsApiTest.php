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
        $item = $run->items()->where('employee_id', $employee->id)->firstOrFail();

        $this->postJson("/api/employee-pay-sheets/{$run->id}/mark-paid", [
            'employee_pay_sheet_item_id' => $item->id,
            'company_account_id' => $account->id,
            'paid_at' => '2026-07-01',
            'reference_number' => 'PAY-2026-06',
        ])->assertOk()
            ->assertJsonPath('message', 'Employee Pay Sheet paid and expense recorded.');

        $item->refresh();
        $run->refresh();
        $expense = Expense::query()->findOrFail($item->expense_id);

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

    public function testPaySheetPaymentsCreateSeparateExpensePerEmployeeNetPay(): void
    {
        $this->actingAsUser(['employee_pay_sheets.manage', 'accounts.manage']);

        $account = $this->createCompanyAccount(['name' => 'Payroll Cash']);
        $employeeOne = Employee::create([
            'name' => 'Nimal Perera',
            'status' => 'active',
            'joined_date' => '2026-01-01',
            'pay_method' => 'daily',
            'daily_rate' => 100,
        ]);
        $employeeTwo = Employee::create([
            'name' => 'Kamal Silva',
            'status' => 'active',
            'joined_date' => '2026-01-01',
            'pay_method' => 'daily',
            'daily_rate' => 150,
        ]);

        $this->postJson('/api/employee-pay-sheets/generate', [
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'company_account_id' => $account->id,
        ])->assertCreated();

        $run = EmployeePaySheetRun::query()->sole();
        $itemOne = $run->items()->where('employee_id', $employeeOne->id)->firstOrFail();
        $itemTwo = $run->items()->where('employee_id', $employeeTwo->id)->firstOrFail();

        $this->assertSame(3000.00, (float) $itemOne->net_pay);
        $this->assertSame(4500.00, (float) $itemTwo->net_pay);

        $this->postJson("/api/employee-pay-sheets/{$run->id}/mark-paid", [
            'employee_pay_sheet_item_id' => $itemOne->id,
            'company_account_id' => $account->id,
            'paid_at' => '2026-07-01',
            'reference_number' => 'PAY-NIMAL-2026-06',
        ])->assertOk();

        $this->postJson("/api/employee-pay-sheets/{$run->id}/mark-paid", [
            'employee_pay_sheet_item_id' => $itemTwo->id,
            'company_account_id' => $account->id,
            'paid_at' => '2026-07-01',
            'reference_number' => 'PAY-KAMAL-2026-06',
        ])->assertOk();

        $itemOne->refresh();
        $itemTwo->refresh();
        $run->refresh();

        $this->assertSame('paid', $itemOne->status);
        $this->assertSame('paid', $itemTwo->status);
        $this->assertSame('paid', $run->status);
        $this->assertNotNull($itemOne->expense_id);
        $this->assertNotNull($itemTwo->expense_id);
        $this->assertNotSame($itemOne->expense_id, $itemTwo->expense_id);

        $expenseOne = Expense::query()->findOrFail($itemOne->expense_id);
        $expenseTwo = Expense::query()->findOrFail($itemTwo->expense_id);

        $this->assertSame('Staff Salaries', $expenseOne->category);
        $this->assertSame('Staff Salaries', $expenseTwo->category);
        $this->assertSame(3000.00, (float) $expenseOne->amount);
        $this->assertSame(4500.00, (float) $expenseTwo->amount);
        $this->assertStringContainsString('Nimal Perera', $expenseOne->notes);
        $this->assertStringContainsString('Kamal Silva', $expenseTwo->notes);

        $this->assertDatabaseHas('company_account_transactions', [
            'company_account_id' => $account->id,
            'model_name' => 'expense',
            'reference_id' => $expenseOne->id,
            'amount' => -3000,
        ]);
        $this->assertDatabaseHas('company_account_transactions', [
            'company_account_id' => $account->id,
            'model_name' => 'expense',
            'reference_id' => $expenseTwo->id,
            'amount' => -4500,
        ]);
        $this->assertSame(2, CompanyAccountTransaction::query()->count());

        $this->deleteJson("/api/accounts/expenses/{$expenseOne->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'This expense is linked to a paid employee pay sheet and cannot be edited or deleted.');
    }
}
