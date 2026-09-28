<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The prep checklist for a job — marinate, load the van, collect the urns.
     *
     * `due_offset_hours` is measured back from the serving time rather than
     * stored as a date, so moving an event moves its whole checklist with it.
     */
    public function up(): void
    {
        Schema::create('order_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('staff_profiles')->nullOnDelete();
            $table->string('title');
            $table->text('detail')->nullable();
            $table->enum('stage', ['prep', 'cook', 'pack', 'transport', 'serve', 'clear'])->default('prep');
            $table->smallInteger('due_offset_hours')->default(0);
            $table->boolean('is_done')->default(false);
            $table->timestamp('done_at')->nullable();
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['order_id', 'position']);
            $table->index(['is_done', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_tasks');
    }
};
