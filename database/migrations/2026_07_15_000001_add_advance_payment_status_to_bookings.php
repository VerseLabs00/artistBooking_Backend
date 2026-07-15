<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Tracks if admin has sent the advance payment to the artist
            $table->enum('advance_payment_status', ['pending', 'sent'])
                  ->default('pending')
                  ->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('advance_payment_status');
        });
    }
};
