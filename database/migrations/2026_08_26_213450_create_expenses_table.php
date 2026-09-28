<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Money out that is not stock: fuel, hire, wages paid, repairs, rent.
     *
     * `order_id` is optional — an expense either belongs to a job (van diesel
     * for a wedding) and lands in that job's margin, or it is an overhead and
     * only shows in the period totals.
     */
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->enum('category', [
                'ingredients', 'packaging', 'fuel', 'vehicle', 'equipment_hire',
                'venue', 'wages', 'utilities', 'rent', 'repairs', 'other',
            ])->default('other');
            $table->unsignedInteger('amount');
            $table->enum('method', ['cash', 'bank_transfer', 'card', 'cheque', 'other'])->default('cash');
            $table->date('spent_on');
            $table->string('reference', 64)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['spent_on', 'category']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
