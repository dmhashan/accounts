<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;

class AppContextService
{
    public function build(User $user, Tenant $tenant): array
    {
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
            ],
            'permissions' => [
                'dashboard' => $user->hasPermission('dashboard.view'),
                'users' => $user->hasPermission('users.view'),
                'members' => $user->hasPermission('users.view'),
                'roles' => $user->hasPermission('roles.view'),
                'settings' => $user->hasPermission('settings.manage'),
                'reports' => $user->hasPermission('reports.view'),
                'inventory' => $user->hasPermission('inventory.manage'),
                'accounts' => $user->hasPermission('accounts.manage'),
                'sales' => $user->hasPermission('sales.process'),
                'stats' => $user->hasPermission('sales.process'),
                'profile' => $user->hasPermission('member.profile.view') || $user->hasRole('member'),
                'workout' => $user->hasPermission('member.workout.view'),
                'diet' => $user->hasPermission('member.diet.view'),
                'payments' => $user->hasPermission('member.payments.view'),
                'attendance' => $user->hasPermission('member.attendance.view'),
            ],
        ];
    }
}
