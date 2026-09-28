<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One counting session. The client counts everything on a Monday and
     * watches fast-moving lines more often, so a count carries the scope it
     * was opened with and stays a draft until it is submitted — a half-finished
     * sheet must never move stock.
     */
    public function up(): void
    {
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 16)->unique();
            $table->date('counted_on');
            $table->enum('scope', ['weekly', 'daily', 'per_event'])->default('weekly');
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->foreignId('opened_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'counted_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_counts');
    }
};
