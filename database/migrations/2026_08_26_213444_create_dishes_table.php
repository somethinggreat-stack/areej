<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The dishes Midland actually cook, as sellable units.
     *
     * Separate from the website's menu config: that file is marketing copy,
     * this is the operational record a quote is priced from and a recipe hangs
     * off. `portion_grams` is what turns "300 guests" into a weight.
     */
    public function up(): void
    {
        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ur')->nullable();
            $table->string('course', 32)->default('mains');
            $table->text('description')->nullable();

            // Selling price per head, in pence.
            $table->unsignedInteger('price_per_head')->default(0);
            $table->unsignedSmallInteger('portion_grams')->default(250);

            $table->boolean('is_vegetarian')->default(false);
            $table->boolean('contains_dairy')->default(false);
            $table->boolean('contains_nuts')->default(false);
            $table->boolean('is_signature')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'course', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dishes');
    }
};
