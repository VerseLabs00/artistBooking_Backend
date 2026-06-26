<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('artist_profile_id')->constrained('artist_profiles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['customer_id', 'artist_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
