<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * The audit trail.
 *
 * Called explicitly from controllers rather than hooked onto model events, so
 * the entry says what a person did ("received delivery PO-1234") rather than
 * what the ORM did ("purchase_order updated"). One is useful in a dispute; the
 * other is noise.
 */
class Activity
{
    /**
     * Fields never worth logging, and fields that must never be logged.
     */
    private const IGNORED = [
        'updated_at', 'created_at', 'remember_token', 'password',
        'two_factor_secret', 'two_factor_recovery_codes',
    ];

    public function log(string $action, string $summary, ?Model $subject = null, array $changes = []): ActivityLog
    {
        $entry = new ActivityLog([
            'user_id' => auth()->id(),
            'action' => $action,
            'summary' => $summary,
            'changes' => $changes === [] ? null : $changes,
            'ip_address' => request()->ip(),
        ]);

        if ($subject !== null) {
            $entry->subject()->associate($subject);
        }

        $entry->save();

        return $entry;
    }

    public function created(Model $subject, string $summary): ActivityLog
    {
        return $this->log('created', $summary, $subject);
    }

    public function deleted(Model $subject, string $summary): ActivityLog
    {
        return $this->log('deleted', $summary, $subject);
    }

    /**
     * Records only the fields that actually moved, with their before and after.
     * A no-op change writes nothing rather than filling the log with noise.
     */
    public function updated(Model $subject, string $summary, array $before = []): ?ActivityLog
    {
        $changes = [];

        foreach ($subject->getChanges() as $field => $after) {
            if (in_array($field, self::IGNORED, true)) {
                continue;
            }

            $changes[$field] = [
                'from' => $before[$field] ?? null,
                'to' => $after,
            ];
        }

        return $changes === [] ? null : $this->log('updated', $summary, $subject, $changes);
    }
}
