<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Equipment out to a job and back again.
     *
     * `quantity_returned` plus `quantity_lost` plus `quantity_damaged` should
     * equal `quantity_out` once a job is closed off; anything less means the
     * van has not been unloaded yet. Keeping lost and damaged apart matters
     * because one is a replacement cost and the other might be a repair.
     */
    public function up(): void
    {
        Schema::create('equipment_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity_out');
            $table->unsignedInteger('quantity_returned')->default(0);
            $table->unsignedInteger('quantity_lost')->default(0);
            $table->unsignedInteger('quantity_damaged')->default(0);
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'equipment_item_id']);
            $table->index('returned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_assignments');
    }
};
