<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A confirmed job. Most orders arrive by phone or WhatsApp rather than
     * through the website, so every field is editable by hand and nothing here
     * depends on an enquiry existing — `enquiry_id` only links the ones that do.
     *
     * Money is stored in pence as integers. Storing currency as a float is how
     * totals end up a penny out after enough additions.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 16)->unique();
            $table->foreignId('enquiry_id')->nullable()->constrained()->nullOnDelete();

            // Customer
            $table->string('customer_name');
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();

            // The job
            $table->string('order_type', 64)->nullable();
            $table->date('event_date');
            $table->time('serve_time')->nullable();
            $table->string('venue')->nullable();
            $table->text('venue_address')->nullable();
            $table->unsignedSmallInteger('guests')->default(0);
            $table->enum('service_style', ['delivery', 'collection', 'delivered_and_served', 'full_buffet', 'not_set'])
                ->default('not_set');
            $table->unsignedTinyInteger('staff_required')->default(0);
            $table->text('menu_notes')->nullable();
            $table->text('dietary')->nullable();

            // Money, in pence
            $table->unsignedInteger('total_amount')->default(0);
            $table->unsignedInteger('deposit_due')->default(0);

            $table->enum('status', ['draft', 'confirmed', 'in_preparation', 'delivered', 'completed', 'cancelled'])
                ->default('draft');
            $table->string('source', 32)->default('phone');
            $table->foreignId('taken_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'event_date']);
            $table->index('event_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
