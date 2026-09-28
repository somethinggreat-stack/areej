<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Holiday, sick and unpaid leave.
     *
     * Stored in hours rather than days because event staff work part days
     * constantly — a five-hour Saturday is not "one day off". The balance on
     * `staff_profiles.holiday_allowance_hours` is measured the same way.
     */
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_profile_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['holiday', 'sick', 'unpaid', 'other'])->default('holiday');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->decimal('hours', 7, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('reason')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();

            $table->index(['staff_profile_id', 'starts_on']);
            $table->index(['status', 'starts_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
