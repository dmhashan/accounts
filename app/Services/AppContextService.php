<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;

class AppContextService
{
    public function __construct(
        private readonly MediaStorageService $media,
        private readonly TenantConfigurationService $config,
        private readonly MemberPortalUrlService $memberPortalUrl,
    ) {}

    public function build(User $user, Tenant $tenant): array
    {
        $cfg = $this->config->all($tenant->id);

        return [
            'settings' => [
                'dateFormat' => $cfg['general.date_format'] ?? 'D MMM YYYY',
                'timeFormat' => $cfg['general.time_format'] ?? 'HH:mm',
                'colorTheme' => $cfg['general.color_theme'] ?? 'crimson',
                'colorMode' => $cfg['general.color_mode'] ?? 'system',
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'address' => $tenant->address,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'logo_url' => $tenant->logo_path ? $this->media->url($tenant->logo_path) : null,
                'member_portal_url' => $this->memberPortalUrl->urlForTenant($tenant),
            ],
            'permissions' => [
                'dashboard' => $this->hasAnyPermission($user, [
                    'dashboard.view',
                    'dashboard.widget.cash_flow',
                    'dashboard.widget.auth_details',
                    'dashboard.widget.stock_availability',
                ]),
                'dashboardWidgetCashFlow' => $this->hasAnyPermission($user, ['dashboard.widget.cash_flow']),
                'dashboardWidgetAuthDetails' => $this->hasAnyPermission($user, ['dashboard.widget.auth_details']),
                'dashboardWidgetStockAvailability' => $this->hasAnyPermission($user, ['dashboard.widget.stock_availability']),

                'members' => $this->hasAnyPermission($user, ['members.view', 'members.temp.view', 'users.view']),
                'membersList' => $this->hasAnyPermission($user, ['members.view', 'users.view']),
                'membersTemp' => $this->hasAnyPermission($user, ['members.temp.view', 'users.view']),
                'membersCreate' => $this->hasAnyPermission($user, ['members.create', 'users.create']),
                'membersEdit' => $this->hasAnyPermission($user, ['members.edit', 'users.edit']),
                'membersDelete' => $this->hasAnyPermission($user, ['members.delete', 'users.delete']),

                'inventory' => $this->hasAnyPermission($user, ['inventory.manage', 'inventory.stock', 'inventory.display', 'inventory.audit']),
                'inventoryProducts' => $this->hasAnyPermission($user, ['inventory.manage']),
                'inventoryStock' => $this->hasAnyPermission($user, ['inventory.stock']),
                'inventoryDisplay' => $this->hasAnyPermission($user, ['inventory.display']),
                'inventoryAudit' => $this->hasAnyPermission($user, ['inventory.audit', 'inventory.stock', 'inventory.display']),

                'accounts' => $this->hasAnyPermission($user, ['accounts.manage', 'accounts.transfers', 'accounts.transactions', 'accounts.adjust']),
                'accountsManage' => $this->hasAnyPermission($user, ['accounts.manage']),
                'accountsTransfers' => $this->hasAnyPermission($user, ['accounts.transfers', 'accounts.manage']),
                'accountsTransactions' => $this->hasAnyPermission($user, ['accounts.transactions', 'accounts.manage']),
                'accountsAdjust' => $this->hasAnyPermission($user, ['accounts.adjust', 'accounts.manage']),
                'expenses' => $this->hasAnyPermission($user, ['expenses.manage', 'accounts.manage']),

                'sales' => $this->hasAnyPermission($user, ['sales.process', 'sales.paid.view']),
                'salesOutstanding' => $this->hasAnyPermission($user, ['sales.process']),
                'salesPaid' => $this->hasAnyPermission($user, ['sales.paid.view', 'sales.process']),
                'salesCreate' => $this->hasAnyPermission($user, ['sales.create']),
                'salesEdit' => $this->hasAnyPermission($user, ['sales.edit']),
                'salesDelete' => $this->hasAnyPermission($user, ['sales.delete']),
                'stats' => $this->hasAnyPermission($user, ['reports.statistics', 'reports.view', 'sales.process', 'accounts.transactions', 'accounts.manage', 'dashboard.widget.cash_flow']),

                'payments' => $this->hasAnyPermission($user, ['payments.manage', 'payment_plans.manage', 'payment_methods.manage', 'member.payments.view']),
                'paymentsManage' => $this->hasAnyPermission($user, ['payments.manage']),
                'paymentPlansManage' => $this->hasAnyPermission($user, ['payment_plans.manage', 'payments.manage']),
                'paymentMethodsManage' => $this->hasAnyPermission($user, ['payment_methods.manage', 'payments.manage']),

                'employeesManage' => $this->hasAnyPermission($user, ['employees.manage']),
                'employeePaySheetsManage' => $this->hasAnyPermission($user, ['employee_pay_sheets.manage']),

                'reports' => $this->hasAnyPermission($user, ['reports.daily_summary', 'reports.real_profit', 'reports.statistics', 'reports.member_analysis', 'reports.customers', 'reports.products', 'reports.view']),
                'reportsDailySummary' => $this->hasAnyPermission($user, ['reports.daily_summary', 'reports.view']),
                'reportsRealProfit' => $this->hasAnyPermission($user, ['reports.real_profit', 'reports.view']),
                'reportsMemberAnalysis' => $this->hasAnyPermission($user, ['reports.member_analysis', 'reports.view']),
                'reportsStatistics' => $this->hasAnyPermission($user, ['reports.statistics', 'reports.view']),
                'reportsCustomers' => $this->hasAnyPermission($user, ['reports.customers', 'reports.view']),
                'reportsProducts' => $this->hasAnyPermission($user, ['reports.products', 'reports.view']),

                'settings' => $this->hasAnyPermission($user, ['settings.manage', 'settings.configuration', 'settings.biometric', 'settings.legacy_tools']),
                'settingsGeneral' => $this->hasAnyPermission($user, ['settings.manage']),
                'settingsConfiguration' => $this->hasAnyPermission($user, ['settings.configuration', 'settings.manage']),
                'settingsBiometric' => $this->hasAnyPermission($user, ['settings.biometric', 'settings.manage']),
                'settingsLegacyTools' => $this->hasAnyPermission($user, ['settings.legacy_tools', 'settings.manage']),
                'users' => $this->hasAnyPermission($user, ['users.view']),
                'usersCreate' => $this->hasAnyPermission($user, ['users.create']),
                'usersEdit' => $this->hasAnyPermission($user, ['users.edit']),
                'usersDelete' => $this->hasAnyPermission($user, ['users.delete']),
                'roles' => $this->hasAnyPermission($user, ['roles.view']),
                'rolesManagePermissions' => $this->hasAnyPermission($user, ['roles.permissions']),

                'workout' => $this->hasAnyPermission($user, ['workouts.manage', 'workouts.exercises', 'workouts.assignments']),
                'memberWorkout' => $this->hasAnyPermission($user, ['member.workout.view']),
                'workoutPrograms' => $this->hasAnyPermission($user, ['workouts.manage']),
                'workoutExercises' => $this->hasAnyPermission($user, ['workouts.exercises', 'workouts.manage']),
                'workoutAssignments' => $this->hasAnyPermission($user, ['workouts.assignments', 'workouts.manage']),

                'notifications' => $this->hasAnyPermission($user, ['notifications.send']),
                'events' => $this->hasAnyPermission($user, ['events.manage']),
                'campaigns' => $this->hasAnyPermission($user, ['campaigns.view']),
                'campaignsCreate' => $this->hasAnyPermission($user, ['campaigns.create']),
                'campaignsEdit' => $this->hasAnyPermission($user, ['campaigns.edit']),
                'campaignsPublish' => $this->hasAnyPermission($user, ['campaigns.publish']),
                'campaignsClose' => $this->hasAnyPermission($user, ['campaigns.close']),
                'campaignsDelete' => $this->hasAnyPermission($user, ['campaigns.delete']),
                'campaignsRegistrations' => $this->hasAnyPermission($user, ['campaigns.registrations', 'campaigns.view']),
                'campaignsVerify' => $this->hasAnyPermission($user, ['campaigns.verify']),
                'activity' => $this->hasAnyPermission($user, ['activity.view']),
                'reconciliation' => $this->hasAnyPermission($user, ['reconciliation.perform', 'reconciliation.manage']),
                'reconciliationPerform' => $this->hasAnyPermission($user, ['reconciliation.perform']),
                'reconciliationManage' => $this->hasAnyPermission($user, ['reconciliation.manage']),
                'vouchersManage' => $this->hasAnyPermission($user, ['vouchers.manage']),
                'formsManage' => $this->hasAnyPermission($user, ['forms.manage']),
            ],
        ];
    }

    private function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }
}
