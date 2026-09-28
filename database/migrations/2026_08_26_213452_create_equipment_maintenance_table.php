<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Servicing and repairs. An item under repair is not available to send out,
     * which is why this has to be visible next to the register rather than
     * living in somebody's notebook.
     */
    public function up(): void
    {
        Schema::create('equipment_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_item_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['service', 'repair', 'inspection', 'replacement'])->default('repair');
            $table->unsignedInteger('quantity')->default(1);
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'written_off'])->default('scheduled');
            $table->date('due_on')->nullable();
            $table->date('completed_on')->nullable();
            $table->unsignedInteger('cost')->default(0);
            $table->string('provider')->nullable();
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_maintenance');
    }
};
