<?php

namespace App\Support;

class SidebarPermissionCatalog
{
    /**
     * Permissions are grouped in the same order as the SPA sidebar.
     *
     * @return array<int, array{label: string, permissions: array<int, array{name: string, slug: string, description: string}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'permissions' => [
                    ['name' => 'Dashboard', 'slug' => 'dashboard.view', 'description' => 'View the dashboard sidebar item and dashboard overview.'],
                ],
            ],
            [
                'label' => 'Members',
                'permissions' => [
                    ['name' => 'Members', 'slug' => 'members.view', 'description' => 'View the Members sidebar item and member list.'],
                    ['name' => 'Temp Members', 'slug' => 'members.temp.view', 'description' => 'View temporary members from the Members sidebar.'],
                    ['name' => 'Create Members', 'slug' => 'members.create', 'description' => 'Create members and temporary members.'],
                    ['name' => 'Edit Members', 'slug' => 'members.edit', 'description' => 'Edit members, documents, measurements, and biometric details.'],
                    ['name' => 'Delete Members', 'slug' => 'members.delete', 'description' => 'Delete members.'],
                ],
            ],
            [
                'label' => 'Inventory',
                'permissions' => [
                    ['name' => 'Products', 'slug' => 'inventory.manage', 'description' => 'View and manage inventory products and variations.'],
                    ['name' => 'Stock', 'slug' => 'inventory.stock', 'description' => 'View and manage stock entries.'],
                    ['name' => 'Display', 'slug' => 'inventory.display', 'description' => 'Release stock to display and manage display quantities.'],
                    ['name' => 'Audit', 'slug' => 'inventory.audit', 'description' => 'View inventory audit logs.'],
                ],
            ],
            [
                'label' => 'Accounts',
                'permissions' => [
                    ['name' => 'Accounts', 'slug' => 'accounts.manage', 'description' => 'View and manage company accounts.'],
                    ['name' => 'Transfers', 'slug' => 'accounts.transfers', 'description' => 'View and manage account transfers.'],
                    ['name' => 'Transactions', 'slug' => 'accounts.transactions', 'description' => 'View account transaction history.'],
                ],
            ],
            [
                'label' => 'Expenses',
                'permissions' => [
                    ['name' => 'Expenses', 'slug' => 'expenses.manage', 'description' => 'View and manage expenses.'],
                ],
            ],
            [
                'label' => 'Sales',
                'permissions' => [
                    ['name' => 'Outstanding', 'slug' => 'sales.process', 'description' => 'View outstanding sales.'],
                    ['name' => 'Paid', 'slug' => 'sales.paid.view', 'description' => 'View paid sales.'],
                    ['name' => 'Create Sales', 'slug' => 'sales.create', 'description' => 'Create new sales.'],
                    ['name' => 'Edit Sales', 'slug' => 'sales.edit', 'description' => 'Edit existing sales and mark sales as paid.'],
                    ['name' => 'Delete Sales', 'slug' => 'sales.delete', 'description' => 'Delete sales.'],
                ],
            ],
            [
                'label' => 'Payments',
                'permissions' => [
                    ['name' => 'Payments', 'slug' => 'payments.manage', 'description' => 'View and manage member payments and wallet top-ups.'],
                    ['name' => 'Payment Plans', 'slug' => 'payment_plans.manage', 'description' => 'View and manage payment plans.'],
                    ['name' => 'Payment Methods', 'slug' => 'payment_methods.manage', 'description' => 'View and manage payment methods and settlement rules.'],
                ],
            ],
            [
                'label' => 'Employees',
                'permissions' => [
                    ['name' => 'Employees', 'slug' => 'employees.manage', 'description' => 'View and manage employees, documents, and attendance.'],
                    ['name' => 'Employee Pay Sheets', 'slug' => 'employee_pay_sheets.manage', 'description' => 'Generate and manage employee pay sheet runs.'],
                ],
            ],
            [
                'label' => 'Reports',
                'permissions' => [
                    ['name' => 'Daily Summary', 'slug' => 'reports.daily_summary', 'description' => 'View, generate, download, and email daily summary reports.'],
                    ['name' => 'Real Profit', 'slug' => 'reports.real_profit', 'description' => 'View, download, and email real profit reports.'],
                    ['name' => 'Statistics', 'slug' => 'reports.view', 'description' => 'View statistics reports.'],
                    ['name' => 'Customers', 'slug' => 'reports.customers', 'description' => 'View customer reports.'],
                    ['name' => 'Products', 'slug' => 'reports.products', 'description' => 'View product reports.'],
                ],
            ],
            [
                'label' => 'Settings',
                'permissions' => [
                    ['name' => 'General', 'slug' => 'settings.manage', 'description' => 'View and update general settings.'],
                    ['name' => 'Users', 'slug' => 'users.view', 'description' => 'View the Users settings subitem and user list.'],
                    ['name' => 'Create Users', 'slug' => 'users.create', 'description' => 'Create users.'],
                    ['name' => 'Edit Users', 'slug' => 'users.edit', 'description' => 'Edit users and user status.'],
                    ['name' => 'Delete Users', 'slug' => 'users.delete', 'description' => 'Delete users.'],
                    ['name' => 'Roles', 'slug' => 'roles.view', 'description' => 'View the Roles settings subitem and role list.'],
                    ['name' => 'Manage Role Permissions', 'slug' => 'roles.permissions', 'description' => 'Create roles and update configurable role permissions.'],
                    ['name' => 'Configuration', 'slug' => 'settings.configuration', 'description' => 'View and update advanced configuration settings.'],
                    ['name' => 'Biometric', 'slug' => 'settings.biometric', 'description' => 'Manage biometric device settings and actions.'],
                    ['name' => 'Manual Commands', 'slug' => 'settings.legacy_tools', 'description' => 'Run manual import and maintenance commands.'],
                ],
            ],
            [
                'label' => 'Workout',
                'permissions' => [
                    ['name' => 'Programs', 'slug' => 'workouts.manage', 'description' => 'View and manage workout programs.'],
                    ['name' => 'Exercises', 'slug' => 'workouts.exercises', 'description' => 'View and manage workout exercises.'],
                    ['name' => 'Assignments', 'slug' => 'workouts.assignments', 'description' => 'View and manage workout assignments.'],
                ],
            ],
            [
                'label' => 'Notifications',
                'permissions' => [
                    ['name' => 'Notifications', 'slug' => 'notifications.send', 'description' => 'View and send bulk notifications.'],
                ],
            ],
            [
                'label' => 'Events',
                'permissions' => [
                    ['name' => 'Events', 'slug' => 'events.manage', 'description' => 'View and manage events and registrations.'],
                ],
            ],
            [
                'label' => 'Activity Logs',
                'permissions' => [
                    ['name' => 'Activity Logs', 'slug' => 'activity.view', 'description' => 'View member activity logs.'],
                ],
            ],
            [
                'label' => 'Reconciliation',
                'permissions' => [
                    ['name' => 'Perform Reconciliation', 'slug' => 'reconciliation.perform', 'description' => 'Open and close daily reconciliation sessions.'],
                    ['name' => 'Manage Reconciliation', 'slug' => 'reconciliation.manage', 'description' => 'Configure reconciliation and view all reconciliation history.'],
                ],
            ],
            [
                'label' => 'Vouchers',
                'permissions' => [
                    ['name' => 'Vouchers', 'slug' => 'vouchers.manage', 'description' => 'View and manage vouchers.'],
                ],
            ],
            [
                'label' => 'Forms',
                'permissions' => [
                    ['name' => 'Forms', 'slug' => 'forms.manage', 'description' => 'View and manage form templates and submissions.'],
                ],
            ],
            [
                'label' => 'Member Portal',
                'permissions' => [
                    ['name' => 'Workout Schedule', 'slug' => 'member.workout.view', 'description' => 'View the member workout schedule sidebar item.'],
                    ['name' => 'Member Payments', 'slug' => 'member.payments.view', 'description' => 'View the member payments sidebar item.'],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{name: string, slug: string, feature: string, description: string}>
     */
    public static function permissions(): array
    {
        $permissions = [];

        foreach (self::groups() as $group) {
            foreach ($group['permissions'] as $permission) {
                $permissions[] = [
                    ...$permission,
                    'feature' => $group['label'],
                ];
            }
        }

        return $permissions;
    }

    /**
     * @return array<int, string>
     */
    public static function slugs(): array
    {
        return array_values(array_map(
            fn (array $permission): string => $permission['slug'],
            self::permissions(),
        ));
    }

    /**
     * @return array<int, string>
     */
    public static function adminRoleSlugs(): array
    {
        return ['super-admin', 'admin', 'owner'];
    }

    /**
     * @return array<int, string>
     */
    public static function memberRolePermissionSlugs(): array
    {
        return [
            'member.workout.view',
            'member.payments.view',
        ];
    }
}
