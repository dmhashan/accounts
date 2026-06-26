<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutProgramAssignment extends Model
{
    protected $fillable = [
        'member_id',
        'source_program_id',
        'assigned_program_id',
        'effective_date',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function sourceProgram(): BelongsTo
    {
        return $this->belongsTo(WorkoutProgram::class, 'source_program_id');
    }

    public function assignedProgram(): BelongsTo
    {
        return $this->belongsTo(WorkoutProgram::class, 'assigned_program_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
