<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A priced offer, sent before there is an order.
     *
     * Deliberately its own record rather than a status on the order: a quote
     * can be declined or expire without a job ever existing, and the same
     * enquiry is often quoted twice at different guest numbers. Accepting one
     * is what creates the order.
     */
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 16)->unique();
            $table->foreignId('enquiry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            $table->string('customer_name');
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();

            $table->string('event_type', 64)->nullable();
            $table->date('event_date')->nullable();
            $table->string('venue')->nullable();
            $table->unsignedSmallInteger('guests')->default(0);
            $table->enum('service_style', ['delivery', 'collection', 'delivered_and_served', 'full_buffet', 'not_set'])
                ->default('not_set');

            // All in pence.
            $table->unsignedInteger('subtotal')->default(0);
            $table->smallInteger('discount_percent')->default(0);
            $table->unsignedInteger('total')->default(0);

            $table->enum('status', ['draft', 'sent', 'accepted', 'declined', 'expired'])->default('draft');
            $table->date('valid_until')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
