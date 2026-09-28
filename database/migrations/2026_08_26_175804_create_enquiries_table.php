<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Website quote requests, captured server-side.
     *
     * The previous site opened the visitor's mail app instead of recording
     * anything, so an enquiry was lost with no trace if they had no mail client
     * or simply closed it. Every call-to-action on the site leads here, so this
     * is the only revenue path — it gets stored first, then emailed.
     */
    public function up(): void
    {
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 16)->unique();
            $table->string('name');
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('event_type', 64)->nullable();
            $table->date('event_date')->nullable();
            $table->unsignedSmallInteger('guests')->nullable();
            $table->string('venue')->nullable();
            $table->string('service_style', 64)->nullable();
            $table->json('extras')->nullable();
            $table->text('dietary')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['new', 'contacted', 'quoted', 'won', 'lost'])->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->string('source', 32)->default('website');
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('event_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
