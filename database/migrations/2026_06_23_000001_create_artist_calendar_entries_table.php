<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artist_calendar_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('artist_profile_id')->constrained('artist_profiles')->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['artist_profile_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artist_calendar_entries');
    }
};
