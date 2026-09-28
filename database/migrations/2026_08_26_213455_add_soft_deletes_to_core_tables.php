<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nothing operational is ever hard deleted.
     *
     * An order carries payments, an item carries a ledger, a supplier carries
     * purchase history — destroying the parent would silently orphan or cascade
     * away records the business is legally expected to keep. Deleting hides;
     * it does not erase.
     */
    private const TABLES = [
        'orders', 'inventory_items', 'suppliers', 'equipment_items',
        'staff_profiles', 'enquiries', 'waste_logs', 'purchase_orders',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            if (Schema::hasTable($name) && ! Schema::hasColumn($name, 'deleted_at')) {
                Schema::table($name, function (Blueprint $table): void {
                    $table->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $name) {
            if (Schema::hasTable($name) && Schema::hasColumn($name, 'deleted_at')) {
                Schema::table($name, function (Blueprint $table): void {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
