<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutProgram extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'duration_weeks',
        'days_per_week',
        'level',
        'status',
        'created_by',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function days(): HasMany
    {
        return $this->hasMany(WorkoutProgramDay::class, 'program_id')->orderBy('day_number');
    }

    public function extras(): HasMany
    {
        return $this->hasMany(WorkoutProgramExtra::class, 'program_id');
    }
}
