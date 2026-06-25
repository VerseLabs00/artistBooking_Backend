<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('platform_fee', 10, 2)->nullable()->after('advance_amount');
            $table->decimal('total_payment', 10, 2)->nullable()->after('platform_fee');
            $table->decimal('commission_rate', 5, 2)->nullable()->after('total_payment');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['platform_fee', 'total_payment', 'commission_rate']);
        });
    }
};