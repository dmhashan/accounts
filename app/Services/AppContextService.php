<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;

class AppContextService
{
    public function __construct(
        private readonly MediaStorageService $media,
        private readonly TenantConfigurationService $config,
    ) {}

    public function build(User $user, Tenant $tenant): array
    {
        $cfg = $this->config->all($tenant->id);

        return [
            'settings' => [
                'dateFormat' => $cfg['general.date_format'] ?? 'D MMM YYYY',
                'timeFormat' => $cfg['general.time_format'] ?? 'HH:mm',
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
            ],
            'permissions' => [
                'dashboard' => $user->hasPermission('dashboard.view'),
                'users' => $user->hasPermission('users.view'),
                'members' => $user->hasPermission('users.view'),
                'roles' => $user->hasPermission('roles.view'),
                'settings' => $user->hasPermission('settings.manage'),
                'reports' => $user->hasPermission('reports.view'),
                'inventory' => $user->hasPermission('inventory.manage'),
                'inventoryStock' => $user->hasPermission('inventory.stock'),
                'inventoryDisplay' => $user->hasPermission('inventory.display'),
                'accounts' => $user->hasPermission('accounts.manage'),
                'expenses' => $user->hasPermission('accounts.manage'),
                'sales' => $user->hasPermission('sales.process'),
                'salesCreate' => $user->hasPermission('sales.create'),
                'salesEdit' => $user->hasPermission('sales.edit'),
                'salesDelete' => $user->hasPermission('sales.delete'),
                'stats' => $user->hasPermission('sales.process'),
                'workout' => $user->hasPermission('workouts.manage'),
                'paymentsManage' => $user->hasPermission('payments.manage'),
                'notifications' => $user->hasPermission('notifications.send'),
                'events' => $user->hasPermission('events.manage'),
                'activity' => $user->hasPermission('activity.view'),
                'reconciliationPerform' => $user->hasPermission('reconciliation.perform'),
                'reconciliationManage' => $user->hasPermission('reconciliation.manage'),
                'vouchersManage' => $user->hasPermission('vouchers.manage'),
                'formsManage' => $user->hasPermission('forms.manage'),
            ],
        ];
    }
}
