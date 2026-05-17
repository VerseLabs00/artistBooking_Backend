<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('artist_profiles', function (Blueprint $table) {
            $table->text('short_bio')->nullable()->after('email');
            $table->text('bio_1')->nullable()->after('short_bio');
            $table->text('bio_2')->nullable()->after('bio_1');
            $table->text('paragraph')->nullable()->after('bio_2');
            $table->json('tags')->nullable()->after('paragraph');
            $table->decimal('starting_price', 10, 2)->nullable()->after('tags');
            $table->decimal('max_price', 10, 2)->nullable()->after('starting_price');
            $table->string('avatar_url')->nullable()->after('max_price');
            $table->string('cover_url')->nullable()->after('avatar_url');
            $table->string('youtube_link')->nullable()->after('cover_url');
            $table->string('facebook_link')->nullable()->after('youtube_link');
            $table->string('instagram_link')->nullable()->after('facebook_link');
            $table->string('spotify_link')->nullable()->after('instagram_link');
        });
    }


    public function down(): void
    {
        Schema::table('artist_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'short_bio', 'bio_1', 'bio_2', 'paragraph', 'tags',
                'starting_price', 'max_price', 'avatar_url', 'cover_url',
                'youtube_link', 'facebook_link', 'instagram_link', 'spotify_link'
            ]);
        });
    }
};
