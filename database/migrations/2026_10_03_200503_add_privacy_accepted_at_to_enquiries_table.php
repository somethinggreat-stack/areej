<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When the person ticked the privacy notice on the enquiry form, so the
     * business can show it was given (UK GDPR accountability).
     */
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->timestamp('privacy_accepted_at')->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropColumn('privacy_accepted_at');
        });
    }
};
