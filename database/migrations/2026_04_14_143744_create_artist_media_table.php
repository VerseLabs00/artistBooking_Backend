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
        Schema::create('artist_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->enum('media_type', ['image', 'video', 'document']);
            $table->enum('purpose', ['verification_front', 'verification_back', 'selfie', 'talent_media']);
            $table->string('url');
            $table->string('cloudinary_public_id')->nullable();
            $table->boolean('is_external_link')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artist_media');
    }
};
