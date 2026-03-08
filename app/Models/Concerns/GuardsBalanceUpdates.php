<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use LogicException;

trait GuardsBalanceUpdates
{
    protected static bool $allowDirectBalanceMutation = false;

    protected static function bootGuardsBalanceUpdates(): void
    {
        static::creating(function (Model $model) {
            $balance = (float) ($model->getAttribute('current_balance') ?? 0);

            if ($balance !== 0.0 && !static::$allowDirectBalanceMutation) {
                throw new LogicException('Direct balance initialization is not allowed. Use transactions.');
            }
        });

        static::updating(function (Model $model) {
            if ($model->isDirty('current_balance') && !static::$allowDirectBalanceMutation) {
                throw new LogicException('Direct balance updates are not allowed. Use transactions.');
            }
        });
    }

    public static function withoutBalanceGuard(callable $callback): mixed
    {
        $previousState = static::$allowDirectBalanceMutation;
        static::$allowDirectBalanceMutation = true;

        try {
            return $callback();
        } finally {
            static::$allowDirectBalanceMutation = $previousState;
        }
    }
}
