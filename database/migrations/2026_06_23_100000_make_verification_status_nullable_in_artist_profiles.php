<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change verification_status to nullable with no default.
        // A profile only becomes 'pending' (visible to admin) after the
        // artist completes all three onboarding steps including talent upload.
        Schema::table('artist_profiles', function (Blueprint $table) {
            $table->string('verification_status')->nullable()->default(null)->change();
        });

        // Existing rows that are still null should stay null (not yet complete).
        // Rows already explicitly set to pending/verified/rejected keep their value.
    }

    public function down(): void
    {
        Schema::table('artist_profiles', function (Blueprint $table) {
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])
                  ->default('pending')
                  ->change();
        });
    }
};