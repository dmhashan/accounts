<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class PaymentPlan extends Model
{
    use SoftDeletes;

    public const UNITS = ['day', 'week', 'month', 'year'];

    protected $fillable = [
        'tenant_id',
        'name',
        'duration_value',
        'duration_unit',
        'price',
        'is_active',
    ];

    protected $casts = [
        'duration_value' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(PaymentMembership::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /**
     * Inclusive end-date for a membership starting on $start.
     * Calendar-aware: 1 month from Jan 15 → Feb 14; 1 day → same day (day pass).
     */
    public function endDateFrom(CarbonInterface|string $start): Carbon
    {
        $date = $start instanceof CarbonInterface ? $start->copy() : Carbon::parse($start);
        $value = max(1, (int) $this->duration_value);

        return match ($this->duration_unit) {
            'year' => $date->addYearsNoOverflow($value)->subDay(),
            'month' => $date->addMonthsNoOverflow($value)->subDay(),
            'week' => $date->addWeeks($value)->subDay(),
            default => $date->addDays($value)->subDay(),
        };
    }

    /**
     * Approximate days — used for grace-period math and sort fallbacks.
     */
    public function approximateDays(): int
    {
        $value = max(1, (int) $this->duration_value);

        return match ($this->duration_unit) {
            'year' => $value * 365,
            'month' => $value * 30,
            'week' => $value * 7,
            default => $value,
        };
    }

    /**
     * SQL expression approximating duration in days, for ORDER BY.
     */
    public static function durationDaysOrderRaw(): string
    {
        return 'duration_value * CASE duration_unit '
            . "WHEN 'year' THEN 365 "
            . "WHEN 'month' THEN 30 "
            . "WHEN 'week' THEN 7 "
            . 'ELSE 1 END';
    }
}
