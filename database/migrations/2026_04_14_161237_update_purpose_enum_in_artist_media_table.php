<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('artist_media', function (Blueprint $table) {
            $table->enum('purpose', ['verification_front', 'verification_back', 'selfie', 'talent_media', 'performance'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('artist_media', function (Blueprint $table) {
            $table->enum('purpose', ['verification_front', 'verification_back', 'selfie', 'talent_media'])->change();
        });
    }
};
