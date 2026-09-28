<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    protected $fillable = [
        'supplier_id', 'purchase_order_id', 'amount',
        'method', 'paid_on', 'reference', 'recorded_by', 'notes',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'paid_on' => 'date'];
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function amountInPounds(): float
    {
        return $this->amount / 100;
    }
}
