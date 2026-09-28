<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Money in, one row per payment.
     *
     * The client asked to "manually update when the money came in", which means
     * part payments are the normal case: a deposit on booking, the balance on
     * the day, sometimes a third instalment. A single `amount_paid` column on
     * the order could not answer "when did the deposit land and who took it",
     * so payments are a ledger and the order's paid total is their sum.
     */
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->enum('method', ['cash', 'bank_transfer', 'card', 'cheque', 'other'])->default('cash');
            $table->enum('kind', ['deposit', 'balance', 'part_payment', 'refund'])->default('part_payment');
            $table->date('paid_on');
            $table->string('reference', 64)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'paid_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
