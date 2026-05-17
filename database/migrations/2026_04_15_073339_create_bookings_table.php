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


            $table->foreignUuid('customer_id')
                  ->constrained('users')
                  ->onDelete('cascade');


            $table->foreignUuid('artist_profile_id')
                  ->constrained('artist_profiles')
                  ->onDelete('cascade');


            $table->date('event_date');
            $table->time('event_start_time');
            $table->decimal('event_duration_hours', 4, 1)->default(2.0);
            $table->string('event_type');
            $table->string('venue');
            $table->text('special_notes')->nullable();


            $table->decimal('agreed_price', 10, 2);
            $table->decimal('advance_amount', 10, 2);


            $table->enum('booking_status', [
                'pending_payment',
                'confirmed',
                'rejected',
                'cancelled',
                'completed',
            ])->default('pending_payment');


            $table->enum('payment_status', [
                'pending',
                'paid',
                'failed',
                'refunded',
            ])->default('pending');


            $table->string('payhere_order_id')->unique();
            $table->string('payhere_payment_id')->nullable();
            $table->string('payhere_status_code')->nullable();
            $table->text('payhere_raw_notify')->nullable();


            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();

            $table->timestamps();


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
