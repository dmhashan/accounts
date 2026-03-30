<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'muscle_group',
        'category',
        'equipment',
        'difficulty',
        'description',
        'status',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function dayExercises(): HasMany
    {
        return $this->hasMany(WorkoutDayExercise::class);
    }
}
