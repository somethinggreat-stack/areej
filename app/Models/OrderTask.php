<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class OrderTask extends Model
{
    protected $fillable = [
        'order_id', 'assigned_to', 'title', 'detail', 'stage',
        'due_offset_hours', 'is_done', 'done_at', 'done_by', 'position',
    ];

    protected function casts(): array
    {
        return [
            'due_offset_hours' => 'integer',
            'is_done' => 'boolean',
            'done_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<StaffProfile, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class, 'assigned_to');
    }

    /** @param  Builder<OrderTask>  $query */
    public function scopeOutstanding(Builder $query): void
    {
        $query->where('is_done', false);
    }

    /**
     * Counted back from the serving time, so moving the event moves the task.
     */
    public function dueAt(): ?Carbon
    {
        $order = $this->order;

        if ($order?->event_date === null) {
            return null;
        }

        $serve = $order->event_date->copy();

        if ($order->serve_time) {
            [$h, $i] = array_pad(explode(':', (string) $order->serve_time), 2, 0);
            $serve->setTime((int) $h, (int) $i);
        } else {
            $serve->setTime(12, 0);
        }

        return $serve->subHours($this->due_offset_hours);
    }

    public function isOverdue(): bool
    {
        $due = $this->dueAt();

        return ! $this->is_done && $due !== null && $due->isPast();
    }

    /** @return array<string, string> */
    public static function stageLabels(): array
    {
        return [
            'prep' => __('Prep'),
            'cook' => __('Cook'),
            'pack' => __('Pack'),
            'transport' => __('Transport'),
            'serve' => __('Serve'),
            'clear' => __('Clear down'),
        ];
    }

    public function stageLabel(): string
    {
        return self::stageLabels()[$this->stage] ?? $this->stage;
    }
}
