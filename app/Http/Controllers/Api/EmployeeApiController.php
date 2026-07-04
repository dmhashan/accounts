<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeDocument;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeApiController extends Controller
{
    public function __construct(private readonly EmployeeService $employeeService) {}

    public function meta(): JsonResponse
    {
        return response()->json($this->employeeService->meta());
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $status = is_string($status) && $status !== '' ? $status : null;

        return response()->json($this->employeeService->index($perPage, $search, $status));
    }

    public function store(Request $request): JsonResponse
    {
        $this->normalizeNameInput($request);

        $employee = $this->employeeService->store($request->validate($this->employeeRules()));

        return response()->json([
            'message' => 'Employee created successfully.',
            'data' => ['id' => $employee->id],
        ], 201);
    }

    public function show(Employee $employee): JsonResponse
    {
        return response()->json([
            'data' => $this->employeeService->show($employee),
        ]);
    }

    public function update(Request $request, Employee $employee): JsonResponse
    {
        $this->normalizeNameInput($request);

        $this->employeeService->update($employee, $request->validate($this->employeeRules($employee)));

        return response()->json([
            'message' => 'Employee updated successfully.',
        ]);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $this->employeeService->destroy($employee);

        return response()->json([
            'message' => 'Employee deleted successfully.',
        ]);
    }

    public function documents(Employee $employee): JsonResponse
    {
        return response()->json($this->employeeService->documents($employee));
    }

    public function storeDocument(Request $request, Employee $employee): JsonResponse
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx,txt',
                'max:' . EmployeeService::MAX_DOCUMENT_SIZE_KB,
            ],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(EmployeeService::DOCUMENT_CATEGORIES))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $document = $this->employeeService->storeDocument(
            $employee,
            $request->user()?->id,
            $validated,
            $request->file('file'),
        );

        return response()->json([
            'message' => 'Document uploaded successfully.',
            'data' => $this->employeeService->serializeDocument($document->load('uploader')),
        ], 201);
    }

    public function documentUrl(Employee $employee, EmployeeDocument $document): JsonResponse
    {
        return response()->json([
            'url' => $this->employeeService->documentUrl($employee, $document),
        ]);
    }

    public function destroyDocument(Employee $employee, EmployeeDocument $document): JsonResponse
    {
        $this->employeeService->destroyDocument($employee, $document);

        return response()->json([
            'message' => 'Document deleted successfully.',
        ]);
    }

    public function attendance(Request $request, Employee $employee): JsonResponse
    {
        $year = max(2000, min(2100, (int) $request->integer('year', now()->year)));
        $month = max(1, min(12, (int) $request->integer('month', now()->month)));

        return response()->json($this->employeeService->attendance($employee, $year, $month));
    }

    public function storeAttendance(Request $request, Employee $employee): JsonResponse
    {
        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(EmployeeService::ATTENDANCE_STATUSES))],
            'check_in_at' => ['nullable', 'date_format:H:i'],
            'check_out_at' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $attendance = $this->employeeService->upsertAttendance($employee, $validated, $request->user()?->id);

        return response()->json([
            'message' => 'Attendance recorded successfully.',
            'data' => $this->employeeService->serializeAttendance($attendance->load('recorder')),
        ]);
    }

    public function destroyAttendance(Employee $employee, EmployeeAttendance $attendance): JsonResponse
    {
        $this->employeeService->destroyAttendance($employee, $attendance);

        return response()->json([
            'message' => 'Attendance removed successfully.',
        ]);
    }

    private function employeeRules(?Employee $employee = null): array
    {
        $employeeCode = Rule::unique('employees', 'employee_code');
        $email = Rule::unique('employees', 'email');

        if ($employee) {
            $employeeCode = $employeeCode->ignore($employee->id);
            $email = $email->ignore($employee->id);
        }

        return [
            'employee_code' => ['nullable', 'string', 'max:50', 'alpha_dash', $employeeCode],
            'name' => ['required', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:255', $email],
            'phone' => ['nullable', 'string', 'max:30'],
            'nic' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'address' => ['nullable', 'string', 'max:1000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['required', Rule::in(array_keys(EmployeeService::EMPLOYMENT_TYPES))],
            'status' => ['required', Rule::in(array_keys(EmployeeService::EMPLOYEE_STATUSES))],
            'joined_date' => ['required', 'date'],
            'left_date' => ['nullable', 'date', 'after_or_equal:joined_date'],
            'pay_method' => ['required', Rule::in(['daily'])],
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'annual_leave_days' => ['nullable', 'numeric', 'min:0', 'max:365'],
            'paid_leave_days_per_month' => ['nullable', 'numeric', 'min:0', 'max:31'],
            'half_paid_leave_days_per_month' => ['nullable', 'numeric', 'min:0', 'max:31'],
            'pay_sheet_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function normalizeNameInput(Request $request): void
    {
        if (filled($request->input('name'))) {
            $request->merge(['name' => trim((string) $request->input('name'))]);

            return;
        }

        $name = trim(
            trim((string) $request->input('first_name', '')) . ' ' . trim((string) $request->input('last_name', '')),
        );

        if ($name !== '') {
            $request->merge(['name' => $name]);
        }
    }
}
