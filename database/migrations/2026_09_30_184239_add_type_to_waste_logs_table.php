<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Waste sorted by a simple type (fresh produce, meat, cooked food…), and
     * waste that is not a stock item — leftover cooked food — written in by
     * name. Existing entries get their type from their stock category.
     */
    public function up(): void
    {
        Schema::table('waste_logs', function (Blueprint $table) {
            $table->string('type', 32)->default('other')->after('id');
            $table->string('description')->nullable()->after('inventory_item_id');
            $table->string('unit', 24)->nullable()->after('quantity');
            $table->foreignId('inventory_item_id')->nullable()->change();

            $table->index(['wasted_on', 'type']);
        });

        $types = [
            'meat_poultry' => ['meat-poultry', 'fish'],
            'fresh_produce' => ['vegetables'],
            'dairy' => ['dairy'],
            'dry_goods' => ['rice-grains', 'flour-bread', 'lentils-pulses', 'spices', 'oil-ghee', 'dessert-ingredients', 'tea-drinks'],
        ];

        foreach ($types as $type => $slugs) {
            DB::table('waste_logs')
                ->whereIn('inventory_item_id', DB::table('inventory_items')
                    ->join('inventory_categories', 'inventory_categories.id', '=', 'inventory_items.inventory_category_id')
                    ->whereIn('inventory_categories.slug', $slugs)
                    ->select('inventory_items.id'))
                ->update(['type' => $type]);
        }
    }

    public function down(): void
    {
        Schema::table('waste_logs', function (Blueprint $table) {
            $table->dropIndex(['wasted_on', 'type']);
            $table->dropColumn(['type', 'description', 'unit']);
        });
    }
};
