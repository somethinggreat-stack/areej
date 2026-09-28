<?php

namespace App\Models;

use App\Services\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Written by {@see Activity}. Read-only everywhere else — an
 * audit trail that can be edited is not an audit trail.
 */
class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'subject_type', 'subject_id',
        'summary', 'changes', 'ip_address',
    ];

    protected function casts(): array
    {
        return ['changes' => 'array'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function subjectLabel(): string
    {
        return class_basename($this->subject_type ?? '') ?: __('System');
    }

    public function tone(): string
    {
        return match ($this->action) {
            'created' => 'good',
            'deleted' => 'bad',
            'updated' => 'info',
            default => 'neutral',
        };
    }
}
