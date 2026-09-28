<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The only thing allowed to change an inventory item's quantity.
 *
 * Every adjustment writes a ledger row and updates the running total inside one
 * transaction, with the item row locked for the duration. Two people counting
 * on their phones at the same time is the normal case in this kitchen, so a
 * read-modify-write without the lock would quietly lose one of the counts.
 */
class StockLedger
{
    /**
     * Move stock by a relative amount. Negative takes stock away.
     */
    public function record(
        InventoryItem $item,
        string $type,
        float $change,
        ?User $by = null,
        ?Model $source = null,
        ?float $unitCost = null,
        ?string $notes = null,
    ): StockMovement {
        return DB::transaction(function () use ($item, $type, $change, $by, $source, $unitCost, $notes) {
            /** @var InventoryItem $locked */
            $locked = InventoryItem::whereKey($item->getKey())->lockForUpdate()->firstOrFail();

            $after = round((float) $locked->current_quantity + $change, 3);

            $locked->forceFill(['current_quantity' => $after])->save();

            $movement = new StockMovement([
                'inventory_item_id' => $locked->getKey(),
                'type' => $type,
                'quantity_change' => round($change, 3),
                'quantity_after' => $after,
                'unit_cost' => $unitCost ?? $locked->unit_cost,
                'recorded_by' => $by?->getKey(),
                'notes' => $notes,
            ]);

            if ($source !== null) {
                $movement->source()->associate($source);
            }

            $movement->save();

            // Keep the caller's instance honest rather than leaving it stale.
            $item->setAttribute('current_quantity', $after);

            return $movement;
        });
    }

    /**
     * Set stock to a known figure, as a physical count does. Returns null when
     * the shelf already agrees with the system — there is nothing to record.
     */
    public function setTo(
        InventoryItem $item,
        float $countedQuantity,
        ?User $by = null,
        ?Model $source = null,
        ?string $notes = null,
    ): ?StockMovement {
        return DB::transaction(function () use ($item, $countedQuantity, $by, $source, $notes) {
            /** @var InventoryItem $locked */
            $locked = InventoryItem::whereKey($item->getKey())->lockForUpdate()->firstOrFail();

            $difference = round($countedQuantity - (float) $locked->current_quantity, 3);

            if (abs($difference) < 0.0005) {
                return null;
            }

            return $this->record(
                $locked,
                StockMovement::TYPE_COUNT,
                $difference,
                $by,
                $source,
                null,
                $notes,
            );
        });
    }
}
