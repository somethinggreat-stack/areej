<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The asset register: chafing dishes, serving dishes, crockery, urns.
     *
     * Kept apart from `inventory_items` on purpose. Stock is consumed and
     * replaced; equipment goes out to a job and is expected back. They answer
     * different questions ("how much is left?" versus "where is it and did it
     * come home?") and merging them would force one of the two to be wrong.
     *
     * `quantity_owned` is how many exist. What is currently out is derived from
     * open assignments rather than stored, so the two can never drift apart.
     */
    public function up(): void
    {
        Schema::create('equipment_items', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ur')->nullable();
            $table->string('category', 64)->default('serving');
            $table->string('asset_tag', 64)->nullable()->unique();
            $table->unsignedInteger('quantity_owned')->default(0);
            $table->unsignedInteger('replacement_cost')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_items');
    }
};
