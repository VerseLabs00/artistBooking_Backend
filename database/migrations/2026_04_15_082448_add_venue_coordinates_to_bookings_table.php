<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('venue_lat', 10, 8)->nullable()->after('venue');
            $table->decimal('venue_lng', 11, 8)->nullable()->after('venue_lat');
        });
    }


    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['venue_lat', 'venue_lng']);
        });
    }
};
