<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_image_path',
        'status',
        'field_config',
        'document_config',
        'published_at',
        'closed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'field_config' => 'array',
        'document_config' => 'array',
        'published_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
