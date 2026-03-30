<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutProgramDay extends Model
{
    protected $fillable = [
        'program_id',
        'day_number',
        'title',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(WorkoutProgram::class, 'program_id');
    }

    public function dayExercises(): HasMany
    {
        return $this->hasMany(WorkoutDayExercise::class, 'day_id')->orderBy('exercise_order');
    }
}
