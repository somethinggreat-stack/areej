<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The client tracks everything: food, packaging and equipment. Equipment is
     * flagged here because it behaves differently — it goes out to an event and
     * is expected back, rather than being consumed.
     */
    public function up(): void
    {
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ur')->nullable();
            $table->string('slug')->unique();
            $table->enum('kind', ['food', 'packaging', 'consumable', 'equipment'])->default('food');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('kind');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_categories');
    }
};
