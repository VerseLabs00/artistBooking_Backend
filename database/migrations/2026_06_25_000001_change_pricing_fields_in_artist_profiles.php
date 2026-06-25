<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artist_profiles', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['starting_price', 'max_price']);
            
            // Add new columns
            $table->decimal('full_price', 10, 2)->nullable()->after('tags');
            $table->decimal('advance', 10, 2)->nullable()->after('full_price');
        });
    }

    public function down(): void
    {
        Schema::table('artist_profiles', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn(['full_price', 'advance']);
            
            // Restore old columns
            $table->decimal('starting_price', 10, 2)->nullable()->after('tags');
            $table->decimal('max_price', 10, 2)->nullable()->after('starting_price');
        });
    }
};
