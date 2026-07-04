<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class EmployeeService
{
    public const EMPLOYMENT_TYPES = [
        'full_time' => 'Full time',
        'part_time' => 'Part time',
        'contractor' => 'Contractor',
        'intern' => 'Intern',
    ];

    public const EMPLOYEE_STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'terminated' => 'Terminated',
    ];

    public const DOCUMENT_CATEGORIES = [
        'identification' => 'Identification',
        'contract' => 'Contract',
        'qualification' => 'Qualification',
        'medical' => 'Medical',
        'pay_sheet' => 'Employee Pay Sheet',
        'other' => 'Other',
    ];

    public const ATTENDANCE_STATUSES = [
        'present' => ['label' => 'Present', 'pay_units' => 1.0, 'leave_units' => 0.0, 'tone' => 'green'],
        'absent' => ['label' => 'Absent', 'pay_units' => 0.0, 'leave_units' => 0.0, 'tone' => 'red'],
        'half_day' => ['label' => 'Half day', 'pay_units' => 0.5, 'leave_units' => 0.5, 'tone' => 'amber'],
        'leave' => ['label' => 'Leave', 'pay_units' => 1.0, 'leave_units' => 1.0, 'tone' => 'blue'],
    ];

    private const LEGACY_ATTENDANCE_STATUSES = [
        'full_paid_leave' => ['label' => 'Full paid leave', 'pay_units' => 1.0, 'leave_units' => 1.0],
        'half_paid_leave' => ['label' => 'Half paid leave', 'pay_units' => 0.5, 'leave_units' => 0.5],
        'no_pay_leave' => ['label' => 'No pay leave', 'pay_units' => 0.0, 'leave_units' => 0.0],
    ];

    public const ALLOWED_DOCUMENT_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
        'image/gif',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
    ];

    public const MAX_DOCUMENT_SIZE_KB = 10240;

    public function __construct(private readonly MediaStorageService $media) {}

    public function meta(): array
    {
        return [
            'employment_types' => $this->options(self::EMPLOYMENT_TYPES),
            'employee_statuses' => $this->options(self::EMPLOYEE_STATUSES),
            'document_categories' => $this->options(self::DOCUMENT_CATEGORIES),
            'attendance_statuses' => collect(self::ATTENDANCE_STATUSES)
                ->map(fn (array $config, string $value) => [
                    'value' => $value,
                    'label' => $config['label'],
                    'pay_units' => $config['pay_units'],
                    'leave_units' => $config['leave_units'],
                    'tone' => $config['tone'],
                ])
                ->values()
                ->all(),
            'pay_methods' => [
                ['value' => 'daily', 'label' => 'Daily rate'],
            ],
            'employees' => Employee::query()
                ->orderBy('name')
                ->get(['id', 'employee_code', 'name', 'job_title', 'department', 'status', 'daily_rate'])
                ->map(fn (Employee $employee) => $this->serializeEmployeeOption($employee))
                ->values()
                ->all(),
        ];
    }

    public function index(int $perPage, string $search = '', ?string $status = null): array
    {
        $employees = Employee::query()
            ->withCount('documents')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('employee_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('job_title', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return [
            'data' => collect($employees->items())->map(fn (Employee $employee) => $this->serializeListEmployee($employee))->values(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
            ],
        ];
    }

    public function store(array $validated): Employee
    {
        $data = $this->normalizeEmployeePayload($validated);

        if (blank($data['employee_code'] ?? null)) {
            $data['employee_code'] = $this->nextEmployeeCode();
        }

        return Employee::create($data);
    }

    public function update(Employee $employee, array $validated): void
    {
        $employee->update($this->normalizeEmployeePayload($validated, $employee));
    }

    public function destroy(Employee $employee): void
    {
        $employee->delete();
    }

    public function show(Employee $employee): array
    {
        $employee->loadCount('documents');

        return $this->serializeEmployee($employee);
    }

    public function documents(Employee $employee): array
    {
        $documents = EmployeeDocument::query()
            ->where('employee_id', $employee->id)
            ->with('uploader:id,name')
            ->orderByDesc('created_at')
            ->get();

        return [
            'data' => $documents->map(fn (EmployeeDocument $document) => $this->serializeDocument($document))->values()->all(),
        ];
    }

    public function storeDocument(Employee $employee, ?int $uploadedBy, array $validated, UploadedFile $file): EmployeeDocument
    {
        $path = $this->media->store($file, "employees/{$employee->id}/documents");

        return EmployeeDocument::create([
            'employee_id' => $employee->id,
            'uploaded_by' => $uploadedBy,
            'name' => trim($validated['name']),
            'category' => $validated['category'] ?? 'other',
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'original_filename' => $file->getClientOriginalName(),
            'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
        ]);
    }

    public function documentUrl(Employee $employee, EmployeeDocument $document): string
    {
        $this->ensureDocumentBelongsToEmployee($employee, $document);

        return $this->media->url($document->path);
    }

    public function destroyDocument(Employee $employee, EmployeeDocument $document): void
    {
        $this->ensureDocumentBelongsToEmployee($employee, $document);

        $this->media->delete($document->path);
        $document->delete();
    }

    public function attendance(Employee $employee, int $year, int $month): array
    {
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = (clone $from)->endOfMonth();

        $records = EmployeeAttendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->with('recorder:id,name')
            ->orderBy('attendance_date')
            ->get();

        $recordsByDate = $records->keyBy(fn (EmployeeAttendance $attendance) => $attendance->attendance_date?->toDateString());
        [$defaultFrom, $defaultTo] = $this->defaultAttendanceRange($employee, $from, $to);
        $calendarRecords = [];

        if ($defaultFrom->lte($defaultTo)) {
            for ($date = $defaultFrom->copy(); $date->lte($defaultTo); $date->addDay()) {
                $dateKey = $date->toDateString();
                $calendarRecords[$dateKey] = $recordsByDate->has($dateKey)
                    ? $this->serializeAttendance($recordsByDate->get($dateKey))
                    : $this->serializeDefaultAttendance($employee, $date);
            }
        }

        foreach ($records as $record) {
            $dateKey = $record->attendance_date?->toDateString();

            if ($dateKey) {
                $calendarRecords[$dateKey] ??= $this->serializeAttendance($record);
            }
        }

        ksort($calendarRecords);

        $stats = collect(array_keys(self::ATTENDANCE_STATUSES))
            ->mapWithKeys(fn (string $status) => [$status => 0])
            ->all();

        foreach ($calendarRecords as $record) {
            $stats[$record['status']] = ($stats[$record['status']] ?? 0) + 1;
        }

        $payableDays = collect($calendarRecords)->sum(fn (array $attendance): float => $this->attendancePayUnits($attendance['status']));

        return [
            'data' => array_values($calendarRecords),
            'stats' => $stats,
            'payable_days' => round($payableDays, 2),
            'leave_balance' => $this->leaveBalance($employee, $this->attendanceBalanceDate($employee, $from, $to)),
            'year' => $year,
            'month' => $month,
        ];
    }

    public function upsertAttendance(Employee $employee, array $validated, ?int $recordedBy): EmployeeAttendance
    {
        $attendanceDate = Carbon::parse($validated['attendance_date'])->startOfDay();

        $this->validateAttendanceDate($employee, $attendanceDate);
        $this->validateLeaveAvailability($employee, $attendanceDate, $validated['status']);

        return EmployeeAttendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'attendance_date' => $attendanceDate->toDateString(),
            ],
            [
                'recorded_by' => $recordedBy,
                'status' => $validated['status'],
                'check_in_at' => filled($validated['check_in_at'] ?? null) ? $validated['check_in_at'] : null,
                'check_out_at' => filled($validated['check_out_at'] ?? null) ? $validated['check_out_at'] : null,
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            ],
        );
    }

    public function destroyAttendance(Employee $employee, EmployeeAttendance $attendance): void
    {
        if ($attendance->employee_id !== $employee->id) {
            abort(404);
        }

        $attendance->delete();
    }

    public function attendancePayUnits(string $status): float
    {
        return (float) (self::ATTENDANCE_STATUSES[$status]['pay_units'] ?? self::LEGACY_ATTENDANCE_STATUSES[$status]['pay_units'] ?? 0);
    }

    public function attendanceLeaveUnits(string $status): float
    {
        return (float) (self::ATTENDANCE_STATUSES[$status]['leave_units'] ?? self::LEGACY_ATTENDANCE_STATUSES[$status]['leave_units'] ?? 0);
    }

    public function serializeEmployeeOption(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'name' => $employee->name,
            'label' => trim(($employee->employee_code ? $employee->employee_code . ' - ' : '') . $employee->name),
            'job_title' => $employee->job_title,
            'department' => $employee->department,
            'status' => $employee->status,
            'daily_rate' => round((float) $employee->daily_rate, 2),
        ];
    }

    public function serializeAttendance(EmployeeAttendance $attendance): array
    {
        return [
            'id' => $attendance->id,
            'employee_id' => $attendance->employee_id,
            'attendance_date' => $attendance->attendance_date?->toDateString(),
            'status' => $attendance->status,
            'status_label' => $this->attendanceStatusLabel($attendance->status),
            'pay_units' => $this->attendancePayUnits($attendance->status),
            'leave_units' => $this->attendanceLeaveUnits($attendance->status),
            'check_in_at' => $attendance->check_in_at,
            'check_out_at' => $attendance->check_out_at,
            'notes' => $attendance->notes,
            'recorded_by' => $attendance->recorder ? ['id' => $attendance->recorder->id, 'name' => $attendance->recorder->name] : null,
            'updated_at' => optional($attendance->updated_at)->format('Y-m-d H:i'),
            'is_default' => false,
        ];
    }

    public function serializeDocument(EmployeeDocument $document): array
    {
        return [
            'id' => $document->id,
            'name' => $document->name,
            'category' => $document->category,
            'category_label' => self::DOCUMENT_CATEGORIES[$document->category] ?? 'Other',
            'mime_type' => $document->mime_type,
            'file_size' => $document->file_size,
            'original_filename' => $document->original_filename,
            'notes' => $document->notes,
            'uploaded_by' => $document->uploader ? ['id' => $document->uploader->id, 'name' => $document->uploader->name] : null,
            'created_at' => optional($document->created_at)->format('d M Y, H:i'),
        ];
    }

    public function serializeEmployee(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'name' => $employee->name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'nic' => $employee->nic,
            'gender' => $employee->gender,
            'date_of_birth' => $employee->date_of_birth?->toDateString(),
            'address' => $employee->address,
            'emergency_contact_name' => $employee->emergency_contact_name,
            'emergency_contact_phone' => $employee->emergency_contact_phone,
            'job_title' => $employee->job_title,
            'department' => $employee->department,
            'employment_type' => $employee->employment_type,
            'employment_type_label' => self::EMPLOYMENT_TYPES[$employee->employment_type] ?? $employee->employment_type,
            'status' => $employee->status,
            'status_label' => self::EMPLOYEE_STATUSES[$employee->status] ?? $employee->status,
            'joined_date' => $employee->joined_date?->toDateString(),
            'left_date' => $employee->left_date?->toDateString(),
            'pay_method' => $employee->pay_method,
            'pay_method_label' => 'Daily rate',
            'daily_rate' => round((float) $employee->daily_rate, 2),
            'annual_leave_days' => round((float) $employee->annual_leave_days, 2),
            'paid_leave_days_per_month' => round((float) $employee->paid_leave_days_per_month, 2),
            'half_paid_leave_days_per_month' => round((float) $employee->half_paid_leave_days_per_month, 2),
            'pay_sheet_notes' => $employee->pay_sheet_notes,
            'documents_count' => $employee->documents_count ?? null,
            'created_at' => optional($employee->created_at)->format('Y-m-d H:i'),
        ];
    }

    private function serializeListEmployee(Employee $employee): array
    {
        return [
            ...$this->serializeEmployee($employee),
            'documents_count' => $employee->documents_count ?? 0,
        ];
    }

    private function normalizeEmployeePayload(array $validated, ?Employee $employee = null): array
    {
        $name = trim((string) $validated['name']);

        return [
            'employee_code' => filled($validated['employee_code'] ?? null) ? trim((string) $validated['employee_code']) : ($employee?->employee_code),
            'name' => $name,
            'email' => filled($validated['email'] ?? null) ? trim((string) $validated['email']) : null,
            'phone' => filled($validated['phone'] ?? null) ? trim((string) $validated['phone']) : null,
            'nic' => filled($validated['nic'] ?? null) ? trim((string) $validated['nic']) : null,
            'gender' => $validated['gender'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'address' => filled($validated['address'] ?? null) ? trim((string) $validated['address']) : null,
            'emergency_contact_name' => filled($validated['emergency_contact_name'] ?? null) ? trim((string) $validated['emergency_contact_name']) : null,
            'emergency_contact_phone' => filled($validated['emergency_contact_phone'] ?? null) ? trim((string) $validated['emergency_contact_phone']) : null,
            'job_title' => filled($validated['job_title'] ?? null) ? trim((string) $validated['job_title']) : null,
            'department' => filled($validated['department'] ?? null) ? trim((string) $validated['department']) : null,
            'employment_type' => $validated['employment_type'] ?? 'full_time',
            'status' => $validated['status'] ?? 'active',
            'joined_date' => $validated['joined_date'],
            'left_date' => $validated['left_date'] ?? null,
            'pay_method' => $validated['pay_method'] ?? 'daily',
            'daily_rate' => $validated['daily_rate'] ?? 0,
            'annual_leave_days' => $validated['annual_leave_days'] ?? ($employee?->annual_leave_days ?? 0),
            'paid_leave_days_per_month' => $validated['paid_leave_days_per_month'] ?? ($employee?->paid_leave_days_per_month ?? 0),
            'half_paid_leave_days_per_month' => $validated['half_paid_leave_days_per_month'] ?? ($employee?->half_paid_leave_days_per_month ?? 0),
            'pay_sheet_notes' => filled($validated['pay_sheet_notes'] ?? null) ? trim((string) $validated['pay_sheet_notes']) : null,
        ];
    }

    private function serializeDefaultAttendance(Employee $employee, Carbon $date): array
    {
        return [
            'id' => null,
            'employee_id' => $employee->id,
            'attendance_date' => $date->toDateString(),
            'status' => 'present',
            'status_label' => self::ATTENDANCE_STATUSES['present']['label'],
            'pay_units' => $this->attendancePayUnits('present'),
            'leave_units' => $this->attendanceLeaveUnits('present'),
            'check_in_at' => null,
            'check_out_at' => null,
            'notes' => null,
            'recorded_by' => null,
            'updated_at' => null,
            'is_default' => true,
        ];
    }

    private function defaultAttendanceRange(Employee $employee, Carbon $from, Carbon $to): array
    {
        $start = $from->copy();
        $end = $to->copy();
        $today = Carbon::today();

        if ($employee->joined_date && $employee->joined_date->gt($start)) {
            $start = $employee->joined_date->copy();
        }

        if ($today->lt($end)) {
            $end = $today;
        }

        if ($employee->left_date && $employee->left_date->lt($end)) {
            $end = $employee->left_date->copy();
        }

        return [$start->startOfDay(), $end->startOfDay()];
    }

    private function attendanceBalanceDate(Employee $employee, Carbon $from, Carbon $to): Carbon
    {
        $date = $to->copy();
        $today = Carbon::today();

        if ($today->betweenIncluded($from, $to)) {
            $date = $today;
        }

        if ($employee->joined_date && $date->lt($employee->joined_date)) {
            return $employee->joined_date->copy()->startOfDay();
        }

        if ($employee->left_date && $date->gt($employee->left_date)) {
            return $employee->left_date->copy()->startOfDay();
        }

        return $date->startOfDay();
    }

    private function validateAttendanceDate(Employee $employee, Carbon $attendanceDate): void
    {
        if ($employee->joined_date && $attendanceDate->lt($employee->joined_date->copy()->startOfDay())) {
            throw ValidationException::withMessages([
                'attendance_date' => ['Attendance cannot be recorded before the employee joined date.'],
            ]);
        }

        if ($employee->left_date && $attendanceDate->gt($employee->left_date->copy()->startOfDay())) {
            throw ValidationException::withMessages([
                'attendance_date' => ['Attendance cannot be recorded after the employee left date.'],
            ]);
        }
    }

    private function validateLeaveAvailability(Employee $employee, Carbon $attendanceDate, string $status): void
    {
        $requiredLeaveUnits = $this->attendanceLeaveUnits($status);

        if ($requiredLeaveUnits <= 0) {
            return;
        }

        $balance = $this->leaveBalance($employee, $attendanceDate, $attendanceDate->toDateString());

        if (((float) $balance['available']) + 0.0001 >= $requiredLeaveUnits) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => [
                sprintf(
                    'Available leave balance is %.2f days. %s needs %.2f days.',
                    (float) $balance['available'],
                    self::ATTENDANCE_STATUSES[$status]['label'] ?? 'This status',
                    $requiredLeaveUnits,
                ),
            ],
        ]);
    }

    private function leaveBalance(Employee $employee, Carbon $date, ?string $excludeDate = null): array
    {
        [$cycleStart, $cycleEnd] = $this->leaveCycleForDate($employee, $date);
        $entitlement = round((float) $employee->annual_leave_days, 2);
        $used = round($this->usedLeaveUnits($employee, $cycleStart, $cycleEnd, $excludeDate), 2);

        return [
            'annual_entitlement' => $entitlement,
            'used' => $used,
            'available' => max(0, round($entitlement - $used, 2)),
            'cycle_start' => $cycleStart,
            'cycle_end' => $cycleEnd,
        ];
    }

    private function leaveCycleForDate(Employee $employee, Carbon $date): array
    {
        $joinedDate = $employee->joined_date?->copy()->startOfDay() ?: $date->copy()->startOfYear();
        $cycleStart = $joinedDate->copy()->year($date->year)->startOfDay();

        if ($cycleStart->gt($date)) {
            $cycleStart->subYear();
        }

        $cycleEnd = $cycleStart->copy()->addYear()->subDay();

        return [$cycleStart->toDateString(), $cycleEnd->toDateString()];
    }

    private function usedLeaveUnits(Employee $employee, string $cycleStart, string $cycleEnd, ?string $excludeDate = null): float
    {
        return EmployeeAttendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$cycleStart, $cycleEnd])
            ->whereIn('status', $this->leaveAttendanceStatuses())
            ->when($excludeDate, fn ($query) => $query->whereDate('attendance_date', '!=', $excludeDate))
            ->get(['status'])
            ->sum(fn (EmployeeAttendance $attendance): float => $this->attendanceLeaveUnits($attendance->status));
    }

    private function leaveAttendanceStatuses(): array
    {
        return collect([...self::ATTENDANCE_STATUSES, ...self::LEGACY_ATTENDANCE_STATUSES])
            ->filter(fn (array $config) => (float) ($config['leave_units'] ?? 0) > 0)
            ->keys()
            ->all();
    }

    private function attendanceStatusLabel(string $status): string
    {
        return self::ATTENDANCE_STATUSES[$status]['label']
            ?? self::LEGACY_ATTENDANCE_STATUSES[$status]['label']
            ?? $status;
    }

    private function nextEmployeeCode(): string
    {
        $nextId = (int) (Employee::withTrashed()->max('id') ?? 0) + 1;

        do {
            $code = 'EMP' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
            $nextId++;
        } while (Employee::withTrashed()->where('employee_code', $code)->exists());

        return $code;
    }

    private function ensureDocumentBelongsToEmployee(Employee $employee, EmployeeDocument $document): void
    {
        if ($document->employee_id !== $employee->id) {
            abort(404);
        }
    }

    private function options(array $items): array
    {
        return collect($items)
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }
}
