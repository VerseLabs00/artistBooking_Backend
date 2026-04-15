<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Who is booking
            $table->foreignUuid('customer_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Who is being booked
            $table->foreignUuid('artist_profile_id')
                  ->constrained('artist_profiles')
                  ->onDelete('cascade');

            // Event details
            $table->date('event_date');
            $table->time('event_start_time');
            $table->decimal('event_duration_hours', 4, 1)->default(2.0);
            $table->string('event_type');          // wedding, birthday, corporate, party, other
            $table->string('venue');
            $table->text('special_notes')->nullable();

            // Pricing
            $table->decimal('agreed_price', 10, 2);     // Full price agreed between parties
            $table->decimal('advance_amount', 10, 2);   // 30% deposit paid via PayHere

            // Booking lifecycle
            $table->enum('booking_status', [
                'pending_payment',   // initiated, waiting for PayHere payment
                'confirmed',         // payment received, booking confirmed
                'rejected',          // artist rejected
                'cancelled',         // customer / artist cancelled
                'completed',         // event happened
            ])->default('pending_payment');

            // Payment lifecycle
            $table->enum('payment_status', [
                'pending',
                'paid',
                'failed',
                'refunded',
            ])->default('pending');

            // PayHere references
            $table->string('payhere_order_id')->unique();          // our generated reference
            $table->string('payhere_payment_id')->nullable();      // PayHere returns this
            $table->string('payhere_status_code')->nullable();     // PayHere webhook status
            $table->text('payhere_raw_notify')->nullable();        // full webhook payload (debugging)

            // Customer contact snapshot (in case user changes their info later)
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index(['customer_id', 'booking_status']);
            $table->index(['artist_profile_id', 'booking_status']);
            $table->index('event_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
