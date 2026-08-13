<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutProgramAssignment extends Model
{
    protected $fillable = [
        'member_id',
        'type',
        'title',
        'source_program_id',
        'assigned_program_id',
        'effective_date',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'formatted_text',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'file_size' => 'integer',
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
