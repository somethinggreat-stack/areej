<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The recipe: how much of each stock item feeds a hundred people.
     *
     * Per hundred rather than per portion because that is how the kitchen
     * already talks and estimates — "two bags of rice does a hundred" — and it
     * keeps the numbers large enough to enter without decimals.
     *
     * This table is what makes "food needed for 300 guests" answerable.
     */
    public function up(): void
    {
        Schema::create('dish_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity_per_100', 12, 3);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['dish_id', 'inventory_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dish_ingredients');
    }
};
