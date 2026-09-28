<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Employment detail for the 40-50 staff. Separated from `users` because
     * casual event staff need a record and a pay rate long before they ever
     * need a login.
     */
    public function up(): void
    {
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('phone', 32)->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'event_staff'])->default('event_staff');
            $table->enum('department', ['kitchen', 'service', 'delivery', 'management'])->default('kitchen');
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->decimal('overtime_rate', 8, 2)->nullable();
            $table->unsignedSmallInteger('holiday_allowance_hours')->default(0);
            $table->string('fingerprint_enroll_id', 32)->nullable()->unique();
            $table->string('clock_pin', 255)->nullable();
            $table->date('started_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['employment_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
    }
};
