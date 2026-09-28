<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The stock ledger. Every change to an item's quantity is written here
     * first, so `inventory_items.current_quantity` is a running total that can
     * always be explained — who changed it, when, by how much and why.
     *
     * `quantity_after` is stored rather than recomputed so history stays
     * readable even after an item is edited or a source record is deleted.
     * `source_type` / `source_id` point back at the count, waste log or
     * purchase order that caused it.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'count_adjustment',
                'purchase',
                'waste',
                'event_usage',
                'manual',
                'opening_balance',
            ]);
            $table->decimal('quantity_change', 12, 3);
            $table->decimal('quantity_after', 12, 3);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->nullableMorphs('source');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['inventory_item_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
