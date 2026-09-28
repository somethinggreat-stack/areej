<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roles drive every permission in the dashboard. The client confirmed
     * attendance records are visible to management and Areej only, so pay and
     * margin data must never reach a staff-level account.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_en');
            $table->string('name_ur')->nullable();
            $table->unsignedTinyInteger('level')->default(10);
            $table->json('abilities')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('email')->constrained()->nullOnDelete();
            $table->string('phone', 32)->nullable()->after('role_id');
            $table->string('locale', 5)->default('en')->after('phone');
            $table->boolean('is_active')->default(true)->after('locale');
            $table->timestamp('last_seen_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id', 'phone', 'locale', 'is_active', 'last_seen_at']);
        });

        Schema::dropIfExists('roles');
    }
};
