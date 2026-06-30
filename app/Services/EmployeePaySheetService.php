<?php

namespace App\Services;

use App\Models\CompanyAccount;
use App\Models\CompanyAccountTransaction;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeePaySheetAdjustment;
use App\Models\EmployeePaySheetItem;
use App\Models\EmployeePaySheetRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class EmployeePaySheetService
{
    private const ADJUSTMENT_CATEGORIES = [
        'salary_advance' => ['label' => 'Salary advance', 'type' => 'deduction'],
        'manual_earning' => ['label' => 'Manual earning', 'type' => 'earning'],
        'manual_deduction' => ['label' => 'Manual deduction', 'type' => 'deduction'],
    ];

    public function __construct(private readonly ExpenseService $expenses) {}

    public function meta(): array
    {
        return [
            'accounts' => CompanyAccount::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (CompanyAccount $account) => [
                    'id' => $account->id,
                    'label' => $account->name,
                    'name' => $account->name,
                ])
                ->values()
                ->all(),
            'adjustment_categories' => collect(self::ADJUSTMENT_CATEGORIES)
                ->map(fn (array $config, string $value) => [
                    'value' => $value,
                    'label' => $config['label'],
                    'type' => $config['type'],
                ])
                ->values()
                ->all(),
        ];
    }

    public function runs(int $perPage): array
    {
        $runs = EmployeePaySheetRun::query()
            ->with(['account:id,name', 'expense:id', 'generator:id,name', 'paidBy:id,name'])
            ->withCount('items')
            ->orderByDesc('period_end')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return [
            'data' => collect($runs->items())->map(fn (EmployeePaySheetRun $run) => $this->serializeRun($run))->values(),
            'meta' => [
                'current_page' => $runs->currentPage(),
                'last_page' => $runs->lastPage(),
                'per_page' => $runs->perPage(),
                'total' => $runs->total(),
            ],
        ];
    }

    public function show(EmployeePaySheetRun $run): array
    {
        $run->load([
            'account:id,name',
            'expense:id',
            'generator:id,name',
            'paidBy:id,name',
            'items' => fn ($query) => $query->orderBy('employee_name'),
        ]);

        return [
            'data' => [
                ...$this->serializeRun($run),
                'items' => $run->items->map(fn (EmployeePaySheetItem $item) => $this->serializeItem($item))->values()->all(),
            ],
        ];
    }

    public function employeeItems(Employee $employee, int $perPage): array
    {
        $baseQuery = EmployeePaySheetItem::query()
            ->where('employee_id', $employee->id)
            ->join('employee_pay_sheet_runs', 'employee_pay_sheet_items.employee_pay_sheet_run_id', '=', 'employee_pay_sheet_runs.id')
            ->select('employee_pay_sheet_items.*')
            ->with('paySheetRun.account:id,name')
            ->orderByDesc('employee_pay_sheet_runs.period_end')
            ->orderByDesc('employee_pay_sheet_runs.created_at');

        $items = (clone $baseQuery)->paginate($perPage);

        return [
            'data' => collect($items->items())
                ->map(fn (EmployeePaySheetItem $item) => $this->serializeEmployeeItem($item))
                ->values()
                ->all(),
            'summary' => [
                'runs_count' => (clone $baseQuery)->count('employee_pay_sheet_items.id'),
                'total_gross' => round((float) (clone $baseQuery)->sum('employee_pay_sheet_items.gross_pay'), 2),
                'total_deductions' => round((float) (clone $baseQuery)->sum('employee_pay_sheet_items.deductions'), 2),
                'total_net' => round((float) (clone $baseQuery)->sum('employee_pay_sheet_items.net_pay'), 2),
            ],
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ];
    }

    public function employeeAdjustments(Employee $employee, string $month, ?string $category = null): array
    {
        [$periodStart, $periodEnd] = $this->periodFromMonth($month);

        $adjustments = $this->monthlyAdjustments($employee, $periodStart, $periodEnd)
            ->when($category, fn ($items) => $items->where('category', $category))
            ->map(fn (EmployeePaySheetAdjustment $adjustment) => $this->serializeAdjustment($adjustment))
            ->values()
            ->all();

        return [
            'data' => $adjustments,
            'summary' => [
                'earnings_total' => round((float) collect($adjustments)->where('type', 'earning')->sum('amount'), 2),
                'deductions_total' => round((float) collect($adjustments)->where('type', 'deduction')->sum('amount'), 2),
                'salary_advance_total' => round((float) collect($adjustments)->where('category', 'salary_advance')->sum('amount'), 2),
            ],
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ];
    }

    public function storeEmployeeAdjustment(Employee $employee, array $validated, ?int $createdBy): array
    {
        [$periodStart, $periodEnd] = $this->periodFromMonth($validated['month']);
        $this->ensurePeriodCanBeAdjusted($periodStart, $periodEnd);

        $category = $validated['category'];
        $config = self::ADJUSTMENT_CATEGORIES[$category];
        $adjustmentDate = filled($validated['adjustment_date'] ?? null)
            ? Carbon::parse($validated['adjustment_date'])->toDateString()
            : null;

        if ($adjustmentDate && ($adjustmentDate < $periodStart || $adjustmentDate > $periodEnd)) {
            abort(422, 'Adjustment date must be inside the selected pay sheet month.');
        }

        return DB::transaction(function () use ($employee, $validated, $createdBy, $periodStart, $periodEnd, $category, $config, $adjustmentDate) {
            $amount = round((float) $validated['amount'], 2);
            $description = filled($validated['description'] ?? null)
                ? trim((string) $validated['description'])
                : $config['label'];
            $notes = filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null;
            $expense = null;

            if ($category === 'salary_advance') {
                $expense = $this->expenses->storeExpense(
                    app('tenant')->id,
                    $this->salaryAdvanceExpensePayload(
                        $employee,
                        (int) $validated['company_account_id'],
                        $amount,
                        $adjustmentDate ?: $periodStart,
                        $description,
                        $notes,
                    ),
                    [],
                    $createdBy,
                );
            }

            $adjustment = EmployeePaySheetAdjustment::create([
                'employee_id' => $employee->id,
                'company_account_id' => $validated['company_account_id'] ?? null,
                'expense_id' => $expense?->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'type' => $config['type'],
                'category' => $category,
                'description' => $description,
                'amount' => $amount,
                'adjustment_date' => $adjustmentDate,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            return [
                'data' => $this->serializeAdjustment($adjustment->fresh(['account:id,name', 'expense:id'])),
            ];
        });
    }

    public function updateEmployeeAdjustment(Employee $employee, EmployeePaySheetAdjustment $adjustment, array $validated, ?int $updatedBy): array
    {
        $this->ensureAdjustmentBelongsToEmployee($employee, $adjustment);
        $this->ensurePeriodCanBeAdjusted(
            $adjustment->period_start->toDateString(),
            $adjustment->period_end->toDateString(),
        );

        [$periodStart, $periodEnd] = $this->periodFromMonth($validated['month']);

        if ($periodStart !== $adjustment->period_start->toDateString() || $periodEnd !== $adjustment->period_end->toDateString()) {
            $this->ensurePeriodCanBeAdjusted($periodStart, $periodEnd);
        }

        $category = $validated['category'];
        $config = self::ADJUSTMENT_CATEGORIES[$category];
        $adjustmentDate = filled($validated['adjustment_date'] ?? null)
            ? Carbon::parse($validated['adjustment_date'])->toDateString()
            : null;

        if ($adjustmentDate && ($adjustmentDate < $periodStart || $adjustmentDate > $periodEnd)) {
            abort(422, 'Adjustment date must be inside the selected pay sheet month.');
        }

        return DB::transaction(function () use ($employee, $adjustment, $validated, $updatedBy, $periodStart, $periodEnd, $category, $config, $adjustmentDate) {
            $lockedAdjustment = EmployeePaySheetAdjustment::query()
                ->lockForUpdate()
                ->findOrFail($adjustment->id);

            $this->ensureAdjustmentBelongsToEmployee($employee, $lockedAdjustment);

            $amount = round((float) $validated['amount'], 2);
            $description = filled($validated['description'] ?? null)
                ? trim((string) $validated['description'])
                : $config['label'];
            $notes = filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null;
            $expense = $lockedAdjustment->expense;

            if ($category === 'salary_advance') {
                $expensePayload = $this->salaryAdvanceExpensePayload(
                    $employee,
                    (int) $validated['company_account_id'],
                    $amount,
                    $adjustmentDate ?: $periodStart,
                    $description,
                    $notes,
                );

                if ($expense) {
                    $this->expenses->updateExpense($expense, app('tenant')->id, $expensePayload, [], $updatedBy);
                    $expense->refresh();
                } else {
                    $expense = $this->expenses->storeExpense(app('tenant')->id, $expensePayload, [], $updatedBy);
                }
            } elseif ($expense) {
                $this->expenses->destroyExpense($expense, app('tenant')->id);
                $expense = null;
            }

            $lockedAdjustment->update([
                'company_account_id' => $category === 'salary_advance' ? $validated['company_account_id'] : null,
                'expense_id' => $expense?->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'type' => $config['type'],
                'category' => $category,
                'description' => $description,
                'amount' => $amount,
                'adjustment_date' => $adjustmentDate,
                'notes' => $notes,
            ]);

            return [
                'data' => $this->serializeAdjustment($lockedAdjustment->fresh(['account:id,name', 'expense:id'])),
            ];
        });
    }

    public function destroyEmployeeAdjustment(Employee $employee, EmployeePaySheetAdjustment $adjustment): void
    {
        $this->ensureAdjustmentBelongsToEmployee($employee, $adjustment);
        $this->ensurePeriodCanBeAdjusted(
            $adjustment->period_start->toDateString(),
            $adjustment->period_end->toDateString(),
        );

        DB::transaction(function () use ($adjustment) {
            $expense = $adjustment->expense;
            $adjustment->delete();

            if ($expense) {
                $this->expenses->destroyExpense($expense, app('tenant')->id);
            }
        });
    }

    public function employeeItemDetail(Employee $employee, EmployeePaySheetItem $item): array
    {
        $this->ensureItemBelongsToEmployee($employee, $item);

        $item->loadMissing('paySheetRun.account');

        return [
            'data' => $this->serializeEmployeeItemDetail($item),
        ];
    }

    public function employeeItemPdf(Employee $employee, EmployeePaySheetItem $item): array
    {
        $this->ensureItemBelongsToEmployee($employee, $item);

        $item->loadMissing('paySheetRun.account');

        $detail = $this->serializeEmployeeItemDetail($item);
        $tenant = app('tenant');
        $html = view('pdfs.employee-pay-sheet', [
            'detail' => $detail,
            'tenantName' => $tenant->name ?? '',
            'tenantAddress' => $tenant->address ?? '',
            'tenantEmail' => $tenant->email ?? '',
            'tenantPhone' => $tenant->phone ?? '',
            'generatedAt' => now()->format('d M Y, H:i'),
        ])->render();

        $defaultFontDirs = (new ConfigVariables)->getDefaults()['fontDir'];
        $defaultFontData = (new FontVariables)->getDefaults()['fontdata'];

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'fontDir' => array_merge($defaultFontDirs, [storage_path('fonts')]),
            'fontdata' => $defaultFontData,
            'default_font' => 'dejavusans',
            'tempDir' => storage_path('app/mpdf-tmp'),
        ]);

        $mpdf->WriteHTML($html);

        $filename = Str::slug('employee-pay-sheet-' . $detail['employee_name'] . '-' . $detail['period_start']) . '.pdf';

        return [
            'content' => $mpdf->Output('', 'S'),
            'filename' => $filename,
        ];
    }

    public function generateEmployeeMonth(Employee $employee, string $month, ?int $generatedBy): array
    {
        $periodStart = Carbon::createFromFormat('!Y-m', $month)->startOfMonth()->toDateString();
        $periodEnd = Carbon::createFromFormat('!Y-m', $month)->endOfMonth()->toDateString();

        $this->ensureEmployeeCanBeGenerated($employee, $periodStart, $periodEnd);

        return DB::transaction(function () use ($employee, $generatedBy, $periodStart, $periodEnd) {
            $run = EmployeePaySheetRun::query()
                ->where('period_start', $periodStart)
                ->where('period_end', $periodEnd)
                ->lockForUpdate()
                ->first();

            if (!$run) {
                $run = EmployeePaySheetRun::create([
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'status' => 'draft',
                    'generated_by' => $generatedBy,
                    'generated_at' => now(),
                ]);
            }

            if ($run->status === 'paid') {
                abort(422, 'Paid employee pay sheets cannot be regenerated.');
            }

            $existingItem = EmployeePaySheetItem::query()
                ->where('employee_pay_sheet_run_id', $run->id)
                ->where('employee_id', $employee->id)
                ->first();

            $item = EmployeePaySheetItem::updateOrCreate(
                [
                    'employee_pay_sheet_run_id' => $run->id,
                    'employee_id' => $employee->id,
                ],
                $this->employeeItemPayload($employee, $run, $periodStart, $periodEnd),
            );

            $run->update([
                'generated_by' => $generatedBy,
                'generated_at' => now(),
            ]);

            $this->refreshRunTotals($run);

            return [
                'action' => $existingItem ? 'regenerated' : 'generated',
                'data' => $this->serializeEmployeeItem($item->fresh('paySheetRun.account')),
            ];
        });
    }

    public function generate(array $validated, ?int $generatedBy): EmployeePaySheetRun
    {
        $periodStart = Carbon::parse($validated['period_start'])->toDateString();
        $periodEnd = Carbon::parse($validated['period_end'])->toDateString();

        if (EmployeePaySheetRun::query()->where('period_start', $periodStart)->where('period_end', $periodEnd)->exists()) {
            abort(422, 'Employee Pay Sheet has already been generated for this period.');
        }

        return DB::transaction(function () use ($validated, $generatedBy, $periodStart, $periodEnd) {
            $run = EmployeePaySheetRun::create([
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => 'draft',
                'company_account_id' => $validated['company_account_id'] ?? null,
                'generated_by' => $generatedBy,
                'generated_at' => now(),
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            ]);

            $employees = Employee::query()
                ->where('status', 'active')
                ->whereDate('joined_date', '<=', $periodEnd)
                ->where(function ($query) use ($periodStart) {
                    $query->whereNull('left_date')
                        ->orWhereDate('left_date', '>=', $periodStart);
                })
                ->orderBy('name')
                ->get();

            $attendanceByEmployee = EmployeeAttendance::query()
                ->whereIn('employee_id', $employees->pluck('id'))
                ->whereBetween('attendance_date', [$periodStart, $periodEnd])
                ->get()
                ->groupBy('employee_id');

            $totalGross = 0.0;
            $totalDeductions = 0.0;
            $totalNet = 0.0;

            foreach ($employees as $employee) {
                $payload = $this->employeeItemPayload($employee, $run, $periodStart, $periodEnd, $attendanceByEmployee->get($employee->id, collect()));

                EmployeePaySheetItem::create($payload);

                $totalGross += $payload['gross_pay'];
                $totalDeductions += $payload['deductions'];
                $totalNet += $payload['net_pay'];
            }

            $run->update([
                'total_gross' => round($totalGross, 2),
                'total_deductions' => round($totalDeductions, 2),
                'total_net' => round($totalNet, 2),
            ]);

            return $run->fresh(['account:id,name', 'generator:id,name'])->loadCount('items');
        });
    }

    public function markPaid(EmployeePaySheetRun $run, array $validated, ?int $paidBy): EmployeePaySheetRun
    {
        if ($run->status === 'paid') {
            abort(422, 'This employee pay sheet is already marked as paid.');
        }

        return DB::transaction(function () use ($run, $validated, $paidBy) {
            $lockedRun = EmployeePaySheetRun::query()
                ->with('expense')
                ->lockForUpdate()
                ->find($run->id);

            if (!$lockedRun) {
                abort(404);
            }

            if ($lockedRun->status === 'paid') {
                abort(422, 'This employee pay sheet is already marked as paid.');
            }

            if ((float) $lockedRun->total_net <= 0) {
                abort(422, 'Employee Pay Sheet net pay must be greater than zero before payment.');
            }

            $paidAt = filled($validated['paid_at'] ?? null) ? Carbon::parse($validated['paid_at']) : now();
            $referenceNumber = filled($validated['reference_number'] ?? null) ? trim((string) $validated['reference_number']) : null;
            $expensePayload = $this->paySheetExpensePayload(
                $lockedRun,
                (int) $validated['company_account_id'],
                $paidAt->toDateString(),
                $referenceNumber,
            );

            if ($lockedRun->expense) {
                $expense = $lockedRun->expense;
                $lockedRun->update(['expense_id' => null]);
                $this->expenses->updateExpense($expense, app('tenant')->id, $expensePayload, [], $paidBy);
                $expense->refresh();
            } else {
                $expense = $this->expenses->storeExpense(app('tenant')->id, $expensePayload, [], $paidBy);
            }

            $lockedRun->update([
                'status' => 'paid',
                'company_account_id' => $validated['company_account_id'],
                'expense_id' => $expense->id,
                'paid_by' => $paidBy,
                'paid_at' => $paidAt,
                'reference_number' => $referenceNumber,
            ]);

            CompanyAccountTransaction::where('model_name', 'employee_pay_sheet')
                ->where('reference_id', $lockedRun->id)
                ->delete();

            return $lockedRun->fresh(['account:id,name', 'expense:id', 'generator:id,name', 'paidBy:id,name'])->loadCount('items');
        });
    }

    public function destroy(EmployeePaySheetRun $run): void
    {
        if ($run->status === 'paid') {
            abort(422, 'Paid employee pay sheets cannot be deleted.');
        }

        DB::transaction(function () use ($run) {
            CompanyAccountTransaction::where('model_name', 'employee_pay_sheet')
                ->where('reference_id', $run->id)
                ->delete();

            $expense = $run->expense;

            if ($expense) {
                $run->update(['expense_id' => null]);
                $this->expenses->destroyExpense($expense, app('tenant')->id);
            }

            $run->delete();
        });
    }

    private function attendanceCounts($records): array
    {
        $counts = collect(array_keys(EmployeeService::ATTENDANCE_STATUSES))
            ->mapWithKeys(fn (string $status) => [$status => 0])
            ->all();

        foreach ($records as $record) {
            $counts[$record->status] = ($counts[$record->status] ?? 0) + 1;
        }

        return $counts;
    }

    private function employeeItemPayload(Employee $employee, EmployeePaySheetRun $run, string $periodStart, string $periodEnd, $records = null): array
    {
        $records ??= EmployeeAttendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$periodStart, $periodEnd])
            ->get();

        $counts = $this->attendanceCounts($records);
        $calculation = $this->paySheetCalculation(
            (float) $employee->daily_rate,
            $periodStart,
            $periodEnd,
            $records,
            $this->monthlyAdjustments($employee, $periodStart, $periodEnd),
        );

        return [
            'employee_pay_sheet_run_id' => $run->id,
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->name,
            'job_title' => $employee->job_title,
            'department' => $employee->department,
            'pay_method' => $employee->pay_method,
            'daily_rate' => $employee->daily_rate,
            'present_days' => $counts['present'],
            'half_day_days' => $counts['half_day'],
            'absent_days' => $counts['absent'],
            'full_paid_leave_days' => ($counts['leave'] ?? 0) + ($counts['full_paid_leave'] ?? 0),
            'half_paid_leave_days' => $counts['half_paid_leave'] ?? 0,
            'no_pay_leave_days' => $counts['no_pay_leave'] ?? 0,
            'payable_days' => round($calculation['payable_days'], 2),
            'gross_pay' => $calculation['total_earnings'],
            'deductions' => $calculation['total_deductions'],
            'net_pay' => $calculation['net_pay'],
            'earning_lines' => $calculation['earning_lines'],
            'deduction_lines' => $calculation['deduction_lines'],
            'notes' => $employee->pay_sheet_notes,
        ];
    }

    private function serializeEmployeeItemDetail(EmployeePaySheetItem $item): array
    {
        $run = $item->paySheetRun;
        $periodStart = $run?->period_start?->toDateString();
        $periodEnd = $run?->period_end?->toDateString();
        $dailyRate = (float) $item->daily_rate;
        $monthDays = $periodStart && $periodEnd ? $this->periodDayCount($periodStart, $periodEnd) : 0;
        $earningLines = $this->normalizePaySheetLines($item->earning_lines);
        $deductionLines = $this->normalizePaySheetLines($item->deduction_lines);

        if ($earningLines === [] && $periodStart && $periodEnd) {
            $calculation = $this->paySheetCalculation(
                $dailyRate,
                $periodStart,
                $periodEnd,
                EmployeeAttendance::query()
                    ->where('employee_id', $item->employee_id)
                    ->whereBetween('attendance_date', [$periodStart, $periodEnd])
                    ->orderBy('attendance_date')
                    ->get(),
                $item->employee_id
                    ? $this->monthlyAdjustmentsByEmployeeId((int) $item->employee_id, $periodStart, $periodEnd)
                    : collect(),
            );
            $earningLines = $calculation['earning_lines'];
            $deductionLines = $calculation['deduction_lines'];
        }

        $basicSalaryLine = collect($earningLines)->firstWhere('category', 'basic_salary');
        $fullSalary = round((float) ($basicSalaryLine['amount'] ?? ($dailyRate * $monthDays)), 2);
        $totalEarnings = round((float) collect($earningLines)->sum('amount'), 2);
        $deductionTotal = round((float) collect($deductionLines)->sum('amount'), 2);

        return [
            ...$this->serializeEmployeeItem($item),
            'month_day_count' => $monthDays,
            'full_salary' => $fullSalary,
            'total_earnings' => $totalEarnings,
            'total_deductions' => $deductionTotal,
            'salary_formula' => [
                'daily_rate' => round($dailyRate, 2),
                'day_count' => $monthDays,
                'amount' => $fullSalary,
            ],
            'earning_lines' => $earningLines,
            'deduction_lines' => $deductionLines,
            'deduction_rows' => $deductionLines,
            'deduction_total' => $deductionTotal,
            'calculated_net' => round($totalEarnings - $deductionTotal, 2),
        ];
    }

    private function paySheetCalculation(float $dailyRate, string $periodStart, string $periodEnd, $records, $adjustments): array
    {
        $monthDays = $this->periodDayCount($periodStart, $periodEnd);
        $fullSalary = round($dailyRate * $monthDays, 2);
        $earningLines = [[
            'type' => 'earning',
            'source' => 'salary',
            'category' => 'basic_salary',
            'label' => 'Basic Salary',
            'description' => 'Daily payment x total month day count',
            'details' => $this->moneyForLine($dailyRate) . ' x ' . $monthDays . ' days',
            'date' => null,
            'dates' => [],
            'quantity' => $monthDays,
            'rate' => round($dailyRate, 2),
            'amount' => $fullSalary,
            'notes' => null,
        ]];

        foreach ($adjustments->where('type', 'earning') as $adjustment) {
            $earningLines[] = $this->adjustmentLine($adjustment);
        }

        $deductionLines = $adjustments
            ->where('type', 'deduction')
            ->map(fn (EmployeePaySheetAdjustment $adjustment) => $this->adjustmentLine($adjustment))
            ->values()
            ->all();

        $attendanceDeductionLines = $this->attendanceDeductionLines($records, $dailyRate);
        $deductionLines = array_values([...$deductionLines, ...$attendanceDeductionLines]);

        $deductionUnits = collect($attendanceDeductionLines)->sum('deduction_units');
        $totalEarnings = round((float) collect($earningLines)->sum('amount'), 2);
        $totalDeductions = round((float) collect($deductionLines)->sum('amount'), 2);

        return [
            'month_day_count' => $monthDays,
            'payable_days' => max(0, $monthDays - $deductionUnits),
            'earning_lines' => $earningLines,
            'deduction_lines' => $deductionLines,
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'net_pay' => round($totalEarnings - $totalDeductions, 2),
        ];
    }

    private function attendanceDeductionLines($records, float $dailyRate): array
    {
        $statuses = [
            'leave' => ['label' => 'Leave', 'deduction_units' => 0.0],
            'half_day' => ['label' => 'Half day', 'deduction_units' => 0.5],
            'absent' => ['label' => 'Absent', 'deduction_units' => 1.0],
            'full_paid_leave' => ['label' => 'Full paid leave', 'deduction_units' => 0.0],
            'half_paid_leave' => ['label' => 'Half paid leave', 'deduction_units' => 0.5],
            'no_pay_leave' => ['label' => 'No pay leave', 'deduction_units' => 1.0],
        ];

        return $records
            ->filter(fn (EmployeeAttendance $attendance) => array_key_exists($attendance->status, $statuses))
            ->sortBy('attendance_date')
            ->map(function (EmployeeAttendance $attendance) use ($statuses, $dailyRate) {
                $date = $attendance->attendance_date?->toDateString();
                $deductionUnits = (float) $statuses[$attendance->status]['deduction_units'];
                $deductionPerDay = round($dailyRate * $deductionUnits, 2);

                return [
                    'type' => 'deduction',
                    'source' => 'attendance',
                    'category' => $attendance->status,
                    'status' => $attendance->status,
                    'label' => $statuses[$attendance->status]['label'],
                    'description' => $statuses[$attendance->status]['label'],
                    'details' => $date ?: '-',
                    'date' => $date,
                    'dates' => $date ? [$date] : [],
                    'dates_label' => $date ?: '',
                    'count' => 1,
                    'quantity' => 1,
                    'rate' => round($dailyRate, 2),
                    'deduction_units' => $deductionUnits,
                    'deduction_per_day' => $deductionPerDay,
                    'amount' => $deductionPerDay,
                    'notes' => $attendance->notes,
                ];
            })
            ->values()
            ->all();
    }

    private function adjustmentLine(EmployeePaySheetAdjustment $adjustment): array
    {
        $category = self::ADJUSTMENT_CATEGORIES[$adjustment->category] ?? [
            'label' => Str::headline($adjustment->category),
            'type' => $adjustment->type,
        ];
        $date = $adjustment->adjustment_date?->toDateString();

        return [
            'type' => $adjustment->type,
            'source' => 'adjustment',
            'category' => $adjustment->category,
            'status' => null,
            'label' => $category['label'],
            'description' => $adjustment->description,
            'details' => $date ?: ($adjustment->notes ?: ''),
            'date' => $date,
            'dates' => $date ? [$date] : [],
            'dates_label' => $date ?: '',
            'count' => 1,
            'quantity' => 1,
            'rate' => round((float) $adjustment->amount, 2),
            'deduction_units' => 0,
            'deduction_per_day' => $adjustment->type === 'deduction' ? round((float) $adjustment->amount, 2) : 0,
            'amount' => round((float) $adjustment->amount, 2),
            'notes' => $adjustment->notes,
        ];
    }

    private function normalizePaySheetLines(?array $lines): array
    {
        return collect($lines ?? [])
            ->map(function (array $line) {
                $amount = round((float) ($line['amount'] ?? 0), 2);

                return [
                    ...$line,
                    'amount' => $amount,
                    'rate' => isset($line['rate']) ? round((float) $line['rate'], 2) : null,
                    'deduction_per_day' => isset($line['deduction_per_day']) ? round((float) $line['deduction_per_day'], 2) : null,
                    'deduction_units' => round((float) ($line['deduction_units'] ?? 0), 2),
                    'dates' => array_values($line['dates'] ?? []),
                    'dates_label' => $line['dates_label'] ?? implode(', ', $line['dates'] ?? []),
                ];
            })
            ->values()
            ->all();
    }

    private function monthlyAdjustments(Employee $employee, string $periodStart, string $periodEnd)
    {
        return $this->monthlyAdjustmentsByEmployeeId($employee->id, $periodStart, $periodEnd);
    }

    private function monthlyAdjustmentsByEmployeeId(int $employeeId, string $periodStart, string $periodEnd)
    {
        return EmployeePaySheetAdjustment::query()
            ->with(['account:id,name', 'expense:id'])
            ->where('employee_id', $employeeId)
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd)
            ->orderBy('adjustment_date')
            ->orderBy('id')
            ->get()
            ->sortBy(fn (EmployeePaySheetAdjustment $adjustment) => sprintf(
                '%02d-%s-%010d',
                $this->adjustmentCategoryOrder($adjustment->category),
                $adjustment->adjustment_date?->toDateString() ?? '',
                $adjustment->id,
            ))
            ->values();
    }

    private function adjustmentCategoryOrder(string $category): int
    {
        return match ($category) {
            'salary_advance' => 10,
            'manual_earning' => 20,
            'manual_deduction' => 30,
            default => 99,
        };
    }

    private function periodFromMonth(string $month): array
    {
        $periodStart = Carbon::createFromFormat('!Y-m', $month)->startOfMonth()->toDateString();
        $periodEnd = Carbon::createFromFormat('!Y-m', $month)->endOfMonth()->toDateString();

        return [$periodStart, $periodEnd];
    }

    private function salaryAdvanceExpensePayload(Employee $employee, int $accountId, float $amount, string $expenseDate, string $description, ?string $notes): array
    {
        return [
            'company_account_id' => $accountId,
            'category' => 'Salary Advance',
            'amount' => $amount,
            'expense_date' => $expenseDate,
            'reference_number' => null,
            'notes' => trim(implode(' - ', array_filter([
                'Salary advance for ' . $employee->name,
                $description,
                $notes,
            ]))),
        ];
    }

    private function paySheetExpensePayload(EmployeePaySheetRun $run, int $accountId, string $expenseDate, ?string $referenceNumber): array
    {
        return [
            'company_account_id' => $accountId,
            'category' => 'Staff Salaries',
            'amount' => round((float) $run->total_net, 2),
            'expense_date' => $expenseDate,
            'reference_number' => $referenceNumber,
            'notes' => 'Employee Pay Sheet: ' . $run->period_start->toDateString() . ' to ' . $run->period_end->toDateString(),
        ];
    }

    private function serializeAdjustment(EmployeePaySheetAdjustment $adjustment): array
    {
        $category = self::ADJUSTMENT_CATEGORIES[$adjustment->category] ?? [
            'label' => Str::headline($adjustment->category),
            'type' => $adjustment->type,
        ];

        return [
            'id' => $adjustment->id,
            'employee_id' => $adjustment->employee_id,
            'company_account_id' => $adjustment->company_account_id,
            'account_name' => $adjustment->account?->name,
            'expense_id' => $adjustment->expense_id,
            'period_start' => $adjustment->period_start?->toDateString(),
            'period_end' => $adjustment->period_end?->toDateString(),
            'type' => $adjustment->type,
            'category' => $adjustment->category,
            'category_label' => $category['label'],
            'description' => $adjustment->description,
            'amount' => round((float) $adjustment->amount, 2),
            'adjustment_date' => $adjustment->adjustment_date?->toDateString(),
            'notes' => $adjustment->notes,
            'created_at' => optional($adjustment->created_at)->format('Y-m-d H:i'),
        ];
    }

    private function moneyForLine(float $amount): string
    {
        return number_format($amount, 2);
    }

    private function periodDayCount(string $periodStart, string $periodEnd): int
    {
        return (int) Carbon::parse($periodStart)->diffInDays(Carbon::parse($periodEnd)) + 1;
    }

    private function ensureEmployeeCanBeGenerated(Employee $employee, string $periodStart, string $periodEnd): void
    {
        if ($employee->status !== 'active') {
            abort(422, 'Only active employees can be generated for employee pay sheets.');
        }

        if ($employee->joined_date && $employee->joined_date->toDateString() > $periodEnd) {
            abort(422, 'Employee joined after the selected month.');
        }

        if ($employee->left_date && $employee->left_date->toDateString() < $periodStart) {
            abort(422, 'Employee left before the selected month.');
        }
    }

    private function ensureItemBelongsToEmployee(Employee $employee, EmployeePaySheetItem $item): void
    {
        if ($item->employee_id !== $employee->id) {
            abort(404);
        }
    }

    private function ensureAdjustmentBelongsToEmployee(Employee $employee, EmployeePaySheetAdjustment $adjustment): void
    {
        if ($adjustment->employee_id !== $employee->id) {
            abort(404);
        }
    }

    private function ensurePeriodCanBeAdjusted(string $periodStart, string $periodEnd): void
    {
        $isPaid = EmployeePaySheetRun::query()
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd)
            ->where('status', 'paid')
            ->exists();

        if ($isPaid) {
            abort(422, 'Paid employee pay sheets cannot be adjusted.');
        }
    }

    private function refreshRunTotals(EmployeePaySheetRun $run): void
    {
        $totals = EmployeePaySheetItem::query()
            ->where('employee_pay_sheet_run_id', $run->id)
            ->selectRaw('COALESCE(SUM(gross_pay), 0) as total_gross')
            ->selectRaw('COALESCE(SUM(deductions), 0) as total_deductions')
            ->selectRaw('COALESCE(SUM(net_pay), 0) as total_net')
            ->first();

        $run->update([
            'total_gross' => round((float) $totals->total_gross, 2),
            'total_deductions' => round((float) $totals->total_deductions, 2),
            'total_net' => round((float) $totals->total_net, 2),
        ]);
    }

    private function serializeRun(EmployeePaySheetRun $run): array
    {
        return [
            'id' => $run->id,
            'period_start' => $run->period_start?->toDateString(),
            'period_end' => $run->period_end?->toDateString(),
            'status' => $run->status,
            'company_account_id' => $run->company_account_id,
            'account_name' => $run->account?->name,
            'expense_id' => $run->expense_id,
            'items_count' => $run->items_count ?? $run->items()->count(),
            'total_gross' => round((float) $run->total_gross, 2),
            'total_deductions' => round((float) $run->total_deductions, 2),
            'total_net' => round((float) $run->total_net, 2),
            'reference_number' => $run->reference_number,
            'notes' => $run->notes,
            'generated_at' => optional($run->generated_at)->format('Y-m-d H:i'),
            'paid_at' => optional($run->paid_at)->format('Y-m-d H:i'),
            'generated_by' => $run->generator ? ['id' => $run->generator->id, 'name' => $run->generator->name] : null,
            'paid_by' => $run->paidBy ? ['id' => $run->paidBy->id, 'name' => $run->paidBy->name] : null,
        ];
    }

    private function serializeItem(EmployeePaySheetItem $item): array
    {
        return [
            'id' => $item->id,
            'employee_id' => $item->employee_id,
            'employee_code' => $item->employee_code,
            'employee_name' => $item->employee_name,
            'job_title' => $item->job_title,
            'department' => $item->department,
            'pay_method' => $item->pay_method,
            'daily_rate' => round((float) $item->daily_rate, 2),
            'present_days' => round((float) $item->present_days, 2),
            'half_day_days' => round((float) $item->half_day_days, 2),
            'absent_days' => round((float) $item->absent_days, 2),
            'full_paid_leave_days' => round((float) $item->full_paid_leave_days, 2),
            'half_paid_leave_days' => round((float) $item->half_paid_leave_days, 2),
            'no_pay_leave_days' => round((float) $item->no_pay_leave_days, 2),
            'payable_days' => round((float) $item->payable_days, 2),
            'gross_pay' => round((float) $item->gross_pay, 2),
            'deductions' => round((float) $item->deductions, 2),
            'net_pay' => round((float) $item->net_pay, 2),
            'notes' => $item->notes,
        ];
    }

    private function serializeEmployeeItem(EmployeePaySheetItem $item): array
    {
        $run = $item->paySheetRun;

        return [
            ...$this->serializeItem($item),
            'employee_pay_sheet_run_id' => $item->employee_pay_sheet_run_id,
            'period_start' => $run?->period_start?->toDateString(),
            'period_end' => $run?->period_end?->toDateString(),
            'status' => $run?->status,
            'account_name' => $run?->account?->name,
            'company_account_id' => $run?->company_account_id,
            'expense_id' => $run?->expense_id,
            'run_total_net' => round((float) ($run?->total_net ?? $item->net_pay), 2),
            'reference_number' => $run?->reference_number,
            'generated_at' => optional($run?->generated_at)->format('Y-m-d H:i'),
            'paid_at' => optional($run?->paid_at)->format('Y-m-d H:i'),
        ];
    }
}
