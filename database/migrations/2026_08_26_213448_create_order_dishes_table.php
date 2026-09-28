<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which dishes a confirmed job is serving, and to how many.
     *
     * Kept apart from `order_items` (which is the priced invoice line) because
     * the kitchen needs the dish and the head count to work out ingredients,
     * while the invoice may bundle everything into "buffet for 300". One is
     * production, the other is billing.
     */
    public function up(): void
    {
        Schema::create('order_dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('guests');
            $table->boolean('ingredients_deducted')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'dish_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_dishes');
    }
};
