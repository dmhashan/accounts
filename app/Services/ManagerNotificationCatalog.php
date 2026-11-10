<?php

namespace App\Services;

class ManagerNotificationCatalog
{
    public const EVENTS = [
        'payment.created' => 'Payment added',
        'payment.updated' => 'Payment edited',
        'payment.deleted' => 'Payment deleted',
        'expense.created' => 'Expense added',
        'expense.updated' => 'Expense edited',
        'expense.deleted' => 'Expense deleted',
        'sale.created' => 'Sale added',
        'sale.updated' => 'Sale edited',
        'sale.deleted' => 'Sale deleted',
        'stock.created' => 'Stock entry added',
        'stock.updated' => 'Stock entry edited',
        'stock.deleted' => 'Stock entry deleted',
        'display.released' => 'Stock released to display',
    ];

    public static function all(): array
    {
        $groups = [
            'payment' => 'Payments',
            'expense' => 'Expenses',
            'sale' => 'Sales',
            'stock' => 'Stock entries',
            'display' => 'Display',
        ];

        $events = [];

        foreach (self::EVENTS as $key => $label) {
            [$group] = explode('.', $key, 2);
            $events[] = [
                'key' => $key,
                'label' => $label,
                'group' => $groups[$group],
            ];
        }

        return $events;
    }

    public static function keys(): array
    {
        return array_keys(self::EVENTS);
    }
}
