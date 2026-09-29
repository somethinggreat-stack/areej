<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wages actually handed over, usually cash, one row per person per payment.
     *
     * Deliberately simple — no tax, NI or payslip fields. The client pays cash
     * weekly and wants a plain record of who was paid what, and when.
     */
    public function up(): void
    {
        Schema::create('wage_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_profile_id')->constrained()->cascadeOnDelete();
            $table->date('week_start')->comment('Monday of the week the pay covers');
            $table->unsignedInteger('amount')->comment('Pence');
            $table->date('paid_on');
            $table->string('method', 16)->default('cash');
            $table->string('notes', 500)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['staff_profile_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wage_payments');
    }
};
