<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A single stock line.
     *
     * `count_frequency` exists because the client counts everything on a Monday
     * but watches fast-moving items more often. `reorder_level` drives the
     * low-stock alerts they asked for.
     */
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_en');
            $table->string('name_ur')->nullable();
            $table->string('sku', 64)->nullable()->unique();
            $table->string('unit', 24)->default('kg');
            $table->decimal('current_quantity', 12, 3)->default(0);
            $table->decimal('reorder_level', 12, 3)->default(0);
            $table->decimal('reorder_quantity', 12, 3)->nullable();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->enum('count_frequency', ['weekly', 'daily', 'per_event'])->default('weekly');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'inventory_category_id']);
            $table->index('count_frequency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
