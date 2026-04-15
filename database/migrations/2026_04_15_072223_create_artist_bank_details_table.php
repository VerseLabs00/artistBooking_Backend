<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bank details stored per artist (one record per artist).
     * Used by the booking process to show payment info to customers.
     *
     * Sensitive fields (account_number) are stored as plain text here.
     * Encrypt at the application layer if required by compliance policy.
     */
    public function up(): void
    {
        Schema::create('artist_bank_details', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // One bank detail record per artist profile
            $table->foreignUuid('artist_profile_id')
                  ->unique()
                  ->constrained('artist_profiles')
                  ->onDelete('cascade');

            // Bank information
            $table->string('account_holder_name');
            $table->string('bank_name');
            $table->string('branch')->nullable();
            $table->string('account_number');
            $table->enum('account_type', ['savings', 'current', 'fixed_deposit'])
                  ->default('savings');

            // Optional: for international / online payments
            $table->string('ifsc_code')->nullable()->comment('IFSC / SWIFT / routing code');

            // Whether these details are verified / ready to be shown to clients
            $table->boolean('is_verified')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artist_bank_details');
    }
};
