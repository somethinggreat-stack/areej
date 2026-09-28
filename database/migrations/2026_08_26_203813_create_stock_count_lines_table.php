<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One line of a count sheet.
     *
     * `expected_quantity` is frozen when the sheet is built so the variance
     * still means something if stock moves while the count is being walked.
     * `counted_quantity` stays null until somebody actually writes a number in,
     * which is what separates "not counted yet" from "counted zero".
     */
    public function up(): void
    {
        Schema::create('stock_count_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('expected_quantity', 12, 3);
            $table->decimal('counted_quantity', 12, 3)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['stock_count_id', 'inventory_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_lines');
    }
};
