<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailySummaryReport extends Model
{
    protected $fillable = [
        'report_date',
        'prepared_by_user_id',
        'prepared_by_name',
        'signature_path',
        'selfie_path',
        'system_snapshot',
        'final_snapshot',
        'changes',
        'totals',
        'pdf_path',
    ];

    protected $casts = [
        'report_date' => 'date',
        'system_snapshot' => 'array',
        'final_snapshot' => 'array',
        'changes' => 'array',
        'totals' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_user_id');
    }
}
