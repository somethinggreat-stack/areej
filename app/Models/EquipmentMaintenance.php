<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentMaintenance extends Model
{
    protected $table = 'equipment_maintenance';

    protected $fillable = [
        'equipment_item_id', 'type', 'quantity', 'status',
        'due_on', 'completed_on', 'cost', 'provider', 'logged_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'cost' => 'integer',
            'due_on' => 'date',
            'completed_on' => 'date',
        ];
    }

    /** @return BelongsTo<EquipmentItem, $this> */
    public function equipmentItem(): BelongsTo
    {
        return $this->belongsTo(EquipmentItem::class);
    }

    /** Anything not finished is still holding stock out of service. */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', ['scheduled', 'in_progress']);
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'completed'
            && $this->due_on !== null
            && $this->due_on->isPast();
    }

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        return [
            'service' => __('Service'),
            'repair' => __('Repair'),
            'inspection' => __('Inspection'),
            'replacement' => __('Replacement'),
        ];
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            'scheduled' => __('Scheduled'),
            'in_progress' => __('In progress'),
            'completed' => __('Completed'),
            'written_off' => __('Written off'),
        ];
    }
}
