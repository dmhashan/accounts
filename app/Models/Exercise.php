<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    protected $fillable = [
        'name',
        'status',
        'default_sets',
        'default_reps',
        'default_tempo',
        'default_rest',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function dayExercises(): HasMany
    {
        return $this->hasMany(WorkoutDayExercise::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ExerciseVariation::class)->orderBy('id');
    }
}
