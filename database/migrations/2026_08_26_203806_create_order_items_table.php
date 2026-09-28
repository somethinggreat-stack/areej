<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What was actually sold, line by line. `description` is free text rather
     * than a menu foreign key because half of these jobs are quoted as
     * "biryani for 300" or "staff, 4 hours" — a rigid menu link would force
     * whoever takes the call to fight the form.
     *
     * `line_total` is stored rather than derived so an agreed price stays
     * agreed even if quantity or unit price is corrected afterwards.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit', 24)->nullable();
            $table->unsignedInteger('unit_price')->default(0);
            $table->unsignedInteger('line_total')->default(0);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['order_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
