<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: modify the enum to add awaiting_confirmation
        DB::statement("ALTER TABLE bookings MODIFY COLUMN booking_status ENUM('awaiting_confirmation','pending_payment','confirmed','rejected','cancelled','completed') NOT NULL DEFAULT 'awaiting_confirmation'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE bookings MODIFY COLUMN booking_status ENUM('pending_payment','confirmed','rejected','cancelled','completed') NOT NULL DEFAULT 'pending_payment'");
    }
};