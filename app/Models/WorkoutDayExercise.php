<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutDayExercise extends Model
{
    protected $fillable = [
        'day_id',
        'exercise_id',
        'display_name',
        'w1_w3_exercise',
        'w2_w4_exercise',
        'sets',
        'reps',
        'tempo',
        'rest_seconds',
        'exercise_order',
    ];

    public function day(): BelongsTo
    {
        return $this->belongsTo(WorkoutProgramDay::class, 'day_id');
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class, 'exercise_id');
    }
}
