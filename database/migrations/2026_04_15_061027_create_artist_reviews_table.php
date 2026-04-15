<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * customer_id  — the user (customer) who left the review.
     *               NULL is allowed so guests can also leave a review
     *               (reviewer_name / reviewer_email cover anonymous cases).
     * artist_profile_id — the artist being reviewed (UUID FK to artist_profiles).
     */
    public function up(): void
    {
        Schema::create('artist_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Who is being reviewed
            $table->foreignUuid('artist_profile_id')
                  ->constrained('artist_profiles')
                  ->onDelete('cascade');

            // Who left the review (nullable — guest reviews allowed)
            $table->foreignUuid('customer_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            // Reviewer display info (useful for guest/anonymous reviews)
            $table->string('reviewer_name')->nullable();
            $table->string('reviewer_email')->nullable();

            // The actual review content
            $table->tinyInteger('rating')->unsigned()->comment('1–5 star rating');
            $table->string('title', 150)->nullable();
            $table->text('body')->nullable();

            // Moderation
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');

            $table->timestamps();

            // One review per customer per artist (unique when customer is logged in)
            $table->unique(['artist_profile_id', 'customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artist_reviews');
    }
};
