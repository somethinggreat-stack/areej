<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One shift — rostered, worked, or both.
     *
     * This table is deliberately also the roster. Late and absent can only mean
     * something against an expected start, so a manager rosters staff to a job
     * (status `scheduled`, `scheduled_start_at` set, no clock in yet) and the
     * shift then either gets clocked into or marked `absent`. Splitting roster
     * and attendance into two tables would make every late/absence question a
     * join and every reconciliation a guess.
     *
     * Three ways a clock-in arrives, per the client:
     *
     *   terminal — the fingerprint unit at Landor Street pushes in and out
     *   roster   — a supervisor at a venue taps present against the job's
     *              roster; time and location come from their phone, so casual
     *              staff need nothing installed on theirs
     *   manual   — somebody forgot, and a manager types it in afterwards
     *
     * `clock_out_at` stays null while a shift is open, which is what makes
     * "who is still clocked in" answerable. `break_minutes` comes off the paid
     * total. `order_id` ties venue work to the job it was for, so labour cost
     * per event falls out of this table rather than needing a second one.
     */
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->date('worked_on');

            // Rostered
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();

            // Actual
            $table->timestamp('clock_in_at')->nullable();
            $table->timestamp('clock_out_at')->nullable();
            $table->unsignedSmallInteger('break_minutes')->default(0);

            $table->enum('method', ['terminal', 'roster', 'manual'])->default('manual');
            $table->enum('status', ['scheduled', 'open', 'closed', 'approved', 'absent'])->default('scheduled');

            // Stamped by the supervisor's phone on a venue check-in.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Frozen when the shift is approved so a later pay rise cannot
            // rewrite weeks that have already been paid.
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->decimal('overtime_rate', 8, 2)->nullable();

            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['staff_profile_id', 'worked_on']);
            $table->index(['worked_on', 'status']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
