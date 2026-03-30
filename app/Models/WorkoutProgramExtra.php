<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutProgramExtra extends Model
{
    protected $fillable = [
        'program_id',
        'type',
        'exercise_name',
        'sets',
        'reps_or_time',
        'rest',
        'notes',
        'frequency_per_week',
        'duration_minutes',
        'cardio_type',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(WorkoutProgram::class, 'program_id');
    }
}
