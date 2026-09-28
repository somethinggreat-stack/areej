<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A quote's discount now reaches the job as a negative line, so the job's
     * lines still add up to the agreed price when a line is added later.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->integer('unit_price')->default(0)->change();
            $table->integer('line_total')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('unit_price')->default(0)->change();
            $table->unsignedInteger('line_total')->default(0)->change();
        });
    }
};
