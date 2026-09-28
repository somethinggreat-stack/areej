<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stock ordered from a supplier. Built from the low-stock list, grouped by
     * supplier so one order covers everything that comes from them.
     *
     * Nothing touches stock until the delivery is marked received — ordering is
     * a promise, not an arrival.
     */
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 16)->unique();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['draft', 'sent', 'part_received', 'received', 'cancelled'])->default('draft');
            $table->date('ordered_on')->nullable();
            $table->date('expected_on')->nullable();
            $table->date('received_on')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'expected_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
