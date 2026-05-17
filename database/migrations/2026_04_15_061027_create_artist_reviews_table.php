<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('artist_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();


            $table->foreignUuid('artist_profile_id')
                  ->constrained('artist_profiles')
                  ->onDelete('cascade');


            $table->foreignUuid('customer_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');


            $table->string('reviewer_name')->nullable();
            $table->string('reviewer_email')->nullable();


            $table->tinyInteger('rating')->unsigned()->comment('1–5 star rating');
            $table->string('title', 150)->nullable();
            $table->text('body')->nullable();


            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');

            $table->timestamps();


            $table->unique(['artist_profile_id', 'customer_id']);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('artist_reviews');
    }
};
