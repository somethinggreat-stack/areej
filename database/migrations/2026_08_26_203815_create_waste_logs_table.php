<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Food written off, and why. The reason matters more than the number —
     * spoilage points at ordering, over-production points at portioning.
     *
     * `unit_cost` is copied in at the time of logging so the cost of waste
     * stays true even after the item is repriced.
     */
    public function up(): void
    {
        Schema::create('waste_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->enum('reason', ['spoilage', 'over_production', 'returned', 'damaged', 'other']);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->date('wasted_on');
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['wasted_on', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_logs');
    }
};
