<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PortalTenantController extends Controller
{
    private const RESERVED_SUBDOMAINS = ['portal', 'admin', 'www', 'mail', 'api', 'central', 'system'];

    /**
     * Return stats for dashboard.
     */
    public function dashboardStats()
    {
        $tenantCount = DB::connection('central')->table('tenants')->count();
        $adminCount = DB::connection('central')->table('portal_users')->count();

        return response()->json([
            'tenant_count' => $tenantCount,
            'admin_count' => $adminCount,
        ]);
    }

    /**
     * List all tenants.
     */
    public function index(Request $request)
    {
        $query = DB::connection('central')->table('tenants');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('subdomain', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $tenants = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($tenants);
    }

    /**
     * Show detailed tenant overview.
     */
    public function show($subdomain)
    {
        $tenant = DB::connection('central')->table('tenants')->where('subdomain', $subdomain)->first();

        if (!$tenant) {
            return response()->json(['message' => 'Tenant not found.'], 404);
        }

        $isolationEnabled = (bool) config('tenancy.database_isolation_enabled', false);
        $uuid = $tenant->database_name;

        $memberSummary = [
            'total_count' => 0,
            'active_count' => 0,
            'inactive_count' => 0,
            'temp_count' => 0,
            'recent' => [],
        ];

        $userSummary = [
            'total_count' => 0,
            'recent' => [],
        ];

        try {
            if ($isolationEnabled && $uuid) {
                // Configure temporary connection
                $centralConfig = config('database.connections.central');
                $tenantConfig = $centralConfig;
                $tenantConfig['database'] = $uuid;
                $tenantConfig['url'] = null;
                config(['database.connections.tenant' => $tenantConfig]);

                DB::purge('tenant');
                DB::reconnect('tenant');

                // Check if table members exists
                $membersExists = DB::connection('tenant')->selectOne(
                    "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'members'",
                    [$uuid],
                );

                if ($membersExists) {
                    $memberSummary['total_count'] = DB::connection('tenant')->table('members')->count();
                    $memberSummary['active_count'] = DB::connection('tenant')->table('members')
                        ->where('is_active', true)->where('is_temp', false)->count();
                    $memberSummary['inactive_count'] = DB::connection('tenant')->table('members')
                        ->where('is_active', false)->where('is_temp', false)->count();
                    $memberSummary['temp_count'] = DB::connection('tenant')->table('members')
                        ->where('is_temp', true)->count();
                    $memberSummary['recent'] = DB::connection('tenant')->table('members')
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get(['name', 'email', 'phone_number', 'joined_date', 'is_active'])
                        ->toArray();

                    // Calculate Member Summary enhancements
                    $memberSummary['new_this_month'] = DB::connection('tenant')->table('members')
                        ->where('created_at', '>=', now()->startOfMonth())
                        ->count();

                    $totalMembers = $memberSummary['total_count'];
                    $activeMembers = DB::connection('tenant')->table('members')->where('is_active', true)->count();
                    $memberSummary['retention_rate'] = $totalMembers > 0 ? round(($activeMembers / $totalMembers) * 100, 1) : 100.0;

                    $sixMonthsAgo = now()->subMonths(5)->startOfMonth();
                    $registrations = DB::connection('tenant')->table('members')
                        ->where('created_at', '>=', $sixMonthsAgo)
                        ->pluck('created_at')
                        ->map(fn ($date) => \Carbon\Carbon::parse($date));

                    $trends = [];

                    for ($i = 5; $i >= 0; $i--) {
                        $monthDate = now()->subMonths($i);
                        $monthKey = $monthDate->format('Y-m');
                        $monthLabel = $monthDate->format('M');
                        $count = $registrations->filter(fn ($date) => $date->format('Y-m') === $monthKey)->count();
                        $trends[] = [
                            'label' => $monthLabel,
                            'count' => $count,
                        ];
                    }
                    $memberSummary['trends'] = $trends;
                }

                // Check if table users exists
                $usersExists = DB::connection('tenant')->selectOne(
                    "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users'",
                    [$uuid],
                );

                if ($usersExists) {
                    $userSummary['total_count'] = DB::connection('tenant')->table('users')->count();
                    $userSummary['recent'] = DB::connection('tenant')->table('users')
                        ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                        ->orderBy('users.created_at', 'desc')
                        ->limit(5)
                        ->get(['users.name', 'users.email', 'roles.name as role_name', 'users.is_active'])
                        ->toArray();

                    // Calculate User Summary enhancements
                    $trainersCount = 0;
                    $otherCount = 0;
                    $rolesList = DB::connection('tenant')->table('users')
                        ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                        ->get(['roles.slug', 'roles.name']);

                    foreach ($rolesList as $r) {
                        $slug = strtolower($r->slug ?? '');
                        $name = strtolower($r->name ?? '');

                        if (str_contains($slug, 'trainer') || str_contains($slug, 'coach') ||
                            str_contains($name, 'trainer') || str_contains($name, 'coach')) {
                            $trainersCount++;
                        } else {
                            $otherCount++;
                        }
                    }
                    $totalStaff = $trainersCount + $otherCount;
                    $userSummary['staff_split'] = [
                        'trainers_percentage' => $totalStaff > 0 ? round(($trainersCount / $totalStaff) * 100, 1) : 0.0,
                        'trainers_count' => $trainersCount,
                        'other_count' => $otherCount,
                    ];

                    $activeStaff = DB::connection('tenant')->table('users')->where('is_active', true)->count();
                    $verifiedStaff = DB::connection('tenant')->table('users')->where('is_active', true)->whereNotNull('email_verified_at')->count();
                    $userSummary['access_security'] = $activeStaff > 0 ? round(($verifiedStaff / $activeStaff) * 100, 1) : 100.0;

                    // Logins activity logs over last 7 days
                    $logins = collect();
                    $auditLogsExists = DB::connection('tenant')->selectOne(
                        "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'audit_logs'",
                        [$uuid],
                    );

                    if ($auditLogsExists) {
                        $sevenDaysAgo = now()->subDays(6)->startOfDay();
                        $logins = DB::connection('tenant')->table('audit_logs')
                            ->where('action', 'user.login')
                            ->where('created_at', '>=', $sevenDaysAgo)
                            ->pluck('created_at')
                            ->map(fn ($date) => \Carbon\Carbon::parse($date));
                    }

                    $activity = [];

                    for ($i = 6; $i >= 0; $i--) {
                        $dayDate = now()->subDays($i);
                        $dayKey = $dayDate->format('Y-m-d');
                        $dayLabel = $dayDate->format('D');
                        $count = $logins->filter(fn ($date) => $date->format('Y-m-d') === $dayKey)->count();
                        $activity[] = [
                            'label' => $dayLabel,
                            'count' => $count,
                        ];
                    }
                    $userSummary['activity'] = $activity;
                }
            } else {
                // Query default connection using tenant domain mapping
                $localTenant = Tenant::where('domain', $subdomain)->first();

                if ($localTenant) {
                    $memberSummary['total_count'] = DB::table('members')->where('tenant_id', $localTenant->id)->count();
                    $memberSummary['active_count'] = DB::table('members')->where('tenant_id', $localTenant->id)
                        ->where('is_active', true)->where('is_temp', false)->count();
                    $memberSummary['inactive_count'] = DB::table('members')->where('tenant_id', $localTenant->id)
                        ->where('is_active', false)->where('is_temp', false)->count();
                    $memberSummary['temp_count'] = DB::table('members')->where('tenant_id', $localTenant->id)
                        ->where('is_temp', true)->count();
                    $memberSummary['recent'] = DB::table('members')
                        ->where('tenant_id', $localTenant->id)
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get(['name', 'email', 'phone_number', 'joined_date', 'is_active'])
                        ->toArray();

                    // Calculate Member Summary enhancements
                    $memberSummary['new_this_month'] = DB::table('members')->where('tenant_id', $localTenant->id)
                        ->where('created_at', '>=', now()->startOfMonth())
                        ->count();

                    $totalMembers = $memberSummary['total_count'];
                    $activeMembers = DB::table('members')->where('tenant_id', $localTenant->id)->where('is_active', true)->count();
                    $memberSummary['retention_rate'] = $totalMembers > 0 ? round(($activeMembers / $totalMembers) * 100, 1) : 100.0;

                    $sixMonthsAgo = now()->subMonths(5)->startOfMonth();
                    $registrations = DB::table('members')->where('tenant_id', $localTenant->id)
                        ->where('created_at', '>=', $sixMonthsAgo)
                        ->pluck('created_at')
                        ->map(fn ($date) => \Carbon\Carbon::parse($date));

                    $trends = [];

                    for ($i = 5; $i >= 0; $i--) {
                        $monthDate = now()->subMonths($i);
                        $monthKey = $monthDate->format('Y-m');
                        $monthLabel = $monthDate->format('M');
                        $count = $registrations->filter(fn ($date) => $date->format('Y-m') === $monthKey)->count();
                        $trends[] = [
                            'label' => $monthLabel,
                            'count' => $count,
                        ];
                    }
                    $memberSummary['trends'] = $trends;

                    $userSummary['total_count'] = DB::table('users')->where('tenant_id', $localTenant->id)->count();
                    $userSummary['recent'] = DB::table('users')
                        ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                        ->where('users.tenant_id', $localTenant->id)
                        ->orderBy('users.created_at', 'desc')
                        ->limit(5)
                        ->get(['users.name', 'users.email', 'roles.name as role_name', 'users.is_active'])
                        ->toArray();

                    // Calculate User Summary enhancements
                    $trainersCount = 0;
                    $otherCount = 0;
                    $rolesList = DB::table('users')->where('users.tenant_id', $localTenant->id)
                        ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                        ->get(['roles.slug', 'roles.name']);

                    foreach ($rolesList as $r) {
                        $slug = strtolower($r->slug ?? '');
                        $name = strtolower($r->name ?? '');

                        if (str_contains($slug, 'trainer') || str_contains($slug, 'coach') ||
                            str_contains($name, 'trainer') || str_contains($name, 'coach')) {
                            $trainersCount++;
                        } else {
                            $otherCount++;
                        }
                    }
                    $totalStaff = $trainersCount + $otherCount;
                    $userSummary['staff_split'] = [
                        'trainers_percentage' => $totalStaff > 0 ? round(($trainersCount / $totalStaff) * 100, 1) : 0.0,
                        'trainers_count' => $trainersCount,
                        'other_count' => $otherCount,
                    ];

                    $activeStaff = DB::table('users')->where('tenant_id', $localTenant->id)->where('is_active', true)->count();
                    $verifiedStaff = DB::table('users')->where('tenant_id', $localTenant->id)->where('is_active', true)->whereNotNull('email_verified_at')->count();
                    $userSummary['access_security'] = $activeStaff > 0 ? round(($verifiedStaff / $activeStaff) * 100, 1) : 100.0;

                    $logins = collect();

                    if (Schema::hasTable('audit_logs')) {
                        $sevenDaysAgo = now()->subDays(6)->startOfDay();
                        $logins = DB::table('audit_logs')->where('tenant_id', $localTenant->id)
                            ->where('action', 'user.login')
                            ->where('created_at', '>=', $sevenDaysAgo)
                            ->pluck('created_at')
                            ->map(fn ($date) => \Carbon\Carbon::parse($date));
                    }

                    $activity = [];

                    for ($i = 6; $i >= 0; $i--) {
                        $dayDate = now()->subDays($i);
                        $dayKey = $dayDate->format('Y-m-d');
                        $dayLabel = $dayDate->format('D');
                        $count = $logins->filter(fn ($date) => $date->format('Y-m-d') === $dayKey)->count();
                        $activity[] = [
                            'label' => $dayLabel,
                            'count' => $count,
                        ];
                    }
                    $userSummary['activity'] = $activity;
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("PortalTenantController: failed querying isolated DB for {$subdomain}: " . $e->getMessage());
        } finally {
            DB::purge('tenant');
        }

        return response()->json([
            'tenant' => $tenant,
            'members' => $memberSummary,
            'users' => $userSummary,
        ]);
    }

    /**
     * Create a new tenant.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => [
                'required',
                'string',
                'max:61',
                'regex:/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/',
            ],
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
        ]);

        $subdomain = strtolower(trim($validated['domain']));

        // Check reserved subdomains
        if (in_array($subdomain, self::RESERVED_SUBDOMAINS)) {
            return response()->json([
                'message' => 'The subdomain you selected is reserved and cannot be used.',
                'errors' => ['domain' => ['This subdomain is reserved.']],
            ], 422);
        }

        // Check if subdomain is already registered in central tenants table
        $exists = DB::connection('central')->table('tenants')->where('subdomain', $subdomain)->exists();

        if ($exists) {
            return response()->json([
                'message' => 'The subdomain has already been taken.',
                'errors' => ['domain' => ['This subdomain is already in use.']],
            ], 422);
        }

        $jobId = (string) Str::uuid();
        $steps = [
            ['key' => 'validate', 'title' => 'Validation & Subdomain Check', 'description' => 'Validating request payload and subdomain reservation', 'status' => 'pending'],
            ['key' => 'central_registry', 'title' => 'Central Registry Setup', 'description' => 'Creating tenant record in central database registry', 'status' => 'pending'],
            ['key' => 'create_database', 'title' => 'Database Provisioning', 'description' => 'Provisioning isolated MySQL database schema', 'status' => 'pending'],
            ['key' => 'migrate_database', 'title' => 'Database Migrations', 'description' => 'Executing database migrations for tenant schema', 'status' => 'pending'],
            ['key' => 'seed_database', 'title' => 'Initial Data Seeding', 'description' => 'Seeding default roles, permissions, and settings', 'status' => 'pending'],
            ['key' => 'finalize', 'title' => 'Finalize Tenant Setup', 'description' => 'Registering details in tenant database and completing setup', 'status' => 'pending'],
        ];

        DB::connection('central')->table('tenant_operation_jobs')->insert([
            'id' => $jobId,
            'tenant_subdomain' => $subdomain,
            'operation' => 'create',
            'status' => 'pending',
            'current_step' => 0,
            'total_steps' => count($steps),
            'steps' => json_encode($steps),
            'payload' => json_encode([
                'name' => $validated['name'],
                'domain' => $subdomain,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \App\Jobs\CreateTenantJob::dispatch($jobId);

        return response()->json([
            'message' => 'Tenant creation queued.',
            'job_id' => $jobId,
            'subdomain' => $subdomain,
        ], 202);
    }

    /**
     * Update tenant details.
     */
    public function update(Request $request, $subdomain)
    {
        $tenant = DB::connection('central')->table('tenants')->where('subdomain', $subdomain)->first();

        if (!$tenant) {
            return response()->json(['message' => 'Tenant not found.'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'is_active' => 'nullable|boolean',
        ]);

        $jobId = (string) Str::uuid();
        $steps = [
            ['key' => 'validate', 'title' => 'Validation', 'description' => 'Validating updated parameters', 'status' => 'pending'],
            ['key' => 'central_registry', 'title' => 'Central Registry Sync', 'description' => 'Updating central tenant registry record', 'status' => 'pending'],
            ['key' => 'tenant_database', 'title' => 'Isolated Database Sync', 'description' => 'Synchronizing details to tenant database', 'status' => 'pending'],
            ['key' => 'finalize', 'title' => 'Finalize Update', 'description' => 'Refreshing tenant status cache and finishing update', 'status' => 'pending'],
        ];

        DB::connection('central')->table('tenant_operation_jobs')->insert([
            'id' => $jobId,
            'tenant_subdomain' => $subdomain,
            'operation' => 'update',
            'status' => 'pending',
            'current_step' => 0,
            'total_steps' => count($steps),
            'steps' => json_encode($steps),
            'payload' => json_encode([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'is_active' => $validated['is_active'] ?? null,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \App\Jobs\UpdateTenantJob::dispatch($jobId);

        return response()->json([
            'message' => 'Tenant update queued.',
            'job_id' => $jobId,
            'subdomain' => $subdomain,
        ], 202);
    }

    /**
     * Delete tenant.
     */
    public function destroy($subdomain)
    {
        $tenant = DB::connection('central')->table('tenants')->where('subdomain', $subdomain)->first();

        if (!$tenant) {
            return response()->json(['message' => 'Tenant not found.'], 404);
        }

        if ($tenant->is_active) {
            return response()->json([
                'message' => 'Cannot delete active tenant. You must suspend/block the tenant first.',
            ], 422);
        }

        $jobId = (string) Str::uuid();
        $steps = [
            ['key' => 'validate', 'title' => 'Eligibility Check', 'description' => 'Validating tenant suspension status', 'status' => 'pending'],
            ['key' => 'drop_database', 'title' => 'Drop Database', 'description' => 'Dropping isolated database schema for tenant', 'status' => 'pending'],
            ['key' => 'local_cleanup', 'title' => 'Local Cleanup', 'description' => 'Removing local tenant representations', 'status' => 'pending'],
            ['key' => 'central_registry', 'title' => 'Central Registry Cleanup', 'description' => 'Removing tenant entry from central registry database', 'status' => 'pending'],
        ];

        DB::connection('central')->table('tenant_operation_jobs')->insert([
            'id' => $jobId,
            'tenant_subdomain' => $subdomain,
            'operation' => 'delete',
            'status' => 'pending',
            'current_step' => 0,
            'total_steps' => count($steps),
            'steps' => json_encode($steps),
            'payload' => json_encode(['subdomain' => $subdomain]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \App\Jobs\DeleteTenantJob::dispatch($jobId);

        return response()->json([
            'message' => 'Tenant deletion queued.',
            'job_id' => $jobId,
            'subdomain' => $subdomain,
        ], 202);
    }

    /**
     * Fetch job status by job ID.
     */
    public function getJobStatus($jobId)
    {
        $job = DB::connection('central')->table('tenant_operation_jobs')->where('id', $jobId)->first();

        if (!$job) {
            return response()->json(['message' => 'Job not found.'], 404);
        }

        $steps = json_decode($job->steps, true) ?: [];
        $totalSteps = count($steps);
        $completedSteps = 0;

        foreach ($steps as $step) {
            if (($step['status'] ?? '') === 'completed') {
                $completedSteps++;
            }
        }

        $progressPercentage = $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100) : 0;

        return response()->json([
            'id' => $job->id,
            'tenant_subdomain' => $job->tenant_subdomain,
            'operation' => $job->operation,
            'status' => $job->status,
            'current_step' => $job->current_step,
            'total_steps' => $job->total_steps,
            'progress_percentage' => $progressPercentage,
            'steps' => $steps,
            'error_message' => $job->error_message,
            'created_at' => $job->created_at,
            'updated_at' => $job->updated_at,
        ]);
    }
}
