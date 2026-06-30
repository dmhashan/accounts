<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeePaySheetAdjustment;
use App\Models\EmployeePaySheetItem;
use App\Models\EmployeePaySheetRun;
use App\Services\EmployeePaySheetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class EmployeePaySheetApiController extends Controller
{
    public function __construct(private readonly EmployeePaySheetService $paySheetService) {}

    public function meta(): JsonResponse
    {
        return response()->json($this->paySheetService->meta());
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 10), 50);

        return response()->json($this->paySheetService->runs($perPage));
    }

    public function employee(Employee $employee, Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        return response()->json($this->paySheetService->employeeItems($employee, $perPage));
    }

    public function generateEmployee(Employee $employee, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $result = $this->paySheetService->generateEmployeeMonth($employee, $validated['month'], $request->user()?->id);

        return response()->json([
            'message' => 'Employee Pay Sheet ' . $result['action'] . ' successfully.',
            'data' => $result['data'],
        ], $result['action'] === 'generated' ? 201 : 200);
    }

    public function employeeAdjustments(Employee $employee, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'category' => ['nullable', Rule::in(['salary_advance', 'manual_earning', 'manual_deduction'])],
        ]);

        return response()->json($this->paySheetService->employeeAdjustments($employee, $validated['month'], $validated['category'] ?? null));
    }

    public function storeEmployeeAdjustment(Employee $employee, Request $request): JsonResponse
    {
        $validated = $this->validateAdjustment($request);

        return response()->json(
            $this->paySheetService->storeEmployeeAdjustment($employee, $validated, $request->user()?->id),
            201,
        );
    }

    public function updateEmployeeAdjustment(Employee $employee, EmployeePaySheetAdjustment $employeePaySheetAdjustment, Request $request): JsonResponse
    {
        $validated = $this->validateAdjustment($request);

        return response()->json(
            $this->paySheetService->updateEmployeeAdjustment($employee, $employeePaySheetAdjustment, $validated, $request->user()?->id),
        );
    }

    public function destroyEmployeeAdjustment(Employee $employee, EmployeePaySheetAdjustment $employeePaySheetAdjustment): JsonResponse
    {
        $this->paySheetService->destroyEmployeeAdjustment($employee, $employeePaySheetAdjustment);

        return response()->json([
            'message' => 'Employee Pay Sheet adjustment deleted successfully.',
        ]);
    }

    public function employeeItem(Employee $employee, EmployeePaySheetItem $employeePaySheetItem): JsonResponse
    {
        return response()->json($this->paySheetService->employeeItemDetail($employee, $employeePaySheetItem));
    }

    public function employeeItemPdf(Employee $employee, EmployeePaySheetItem $employeePaySheetItem): Response
    {
        $pdf = $this->paySheetService->employeeItemPdf($employee, $employeePaySheetItem);

        return response($pdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $pdf['filename'] . '"',
        ]);
    }

    public function show(EmployeePaySheetRun $employeePaySheetRun): JsonResponse
    {
        return response()->json($this->paySheetService->show($employeePaySheetRun));
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'company_account_id' => ['nullable', 'integer', 'exists:company_accounts,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $run = $this->paySheetService->generate($validated, $request->user()?->id);

        return response()->json([
            'message' => 'Employee Pay Sheet generated successfully.',
            'data' => ['id' => $run->id],
        ], 201);
    }

    public function markPaid(Request $request, EmployeePaySheetRun $employeePaySheetRun): JsonResponse
    {
        $validated = $request->validate([
            'company_account_id' => ['required', 'integer', 'exists:company_accounts,id'],
            'paid_at' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
        ]);

        $run = $this->paySheetService->markPaid($employeePaySheetRun, $validated, $request->user()?->id);

        return response()->json([
            'message' => 'Employee Pay Sheet paid and expense recorded.',
            'data' => ['id' => $run->id],
        ]);
    }

    public function destroy(EmployeePaySheetRun $employeePaySheetRun): JsonResponse
    {
        $this->paySheetService->destroy($employeePaySheetRun);

        return response()->json([
            'message' => 'Employee Pay Sheet run deleted successfully.',
        ]);
    }

    private function validateAdjustment(Request $request): array
    {
        return $request->validate([
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'category' => ['required', Rule::in(['salary_advance', 'manual_earning', 'manual_deduction'])],
            'company_account_id' => ['nullable', 'required_if:category,salary_advance', 'integer', 'exists:company_accounts,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'adjustment_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
