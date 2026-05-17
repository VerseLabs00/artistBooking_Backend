<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('artist_bank_details', function (Blueprint $table) {
            $table->uuid('id')->primary();


            $table->foreignUuid('artist_profile_id')
                  ->unique()
                  ->constrained('artist_profiles')
                  ->onDelete('cascade');


            $table->string('account_holder_name');
            $table->string('bank_name');
            $table->string('branch')->nullable();
            $table->string('account_number');
            $table->enum('account_type', ['savings', 'current', 'fixed_deposit'])
                  ->default('savings');


            $table->string('ifsc_code')->nullable()->comment('IFSC / SWIFT / routing code');


            $table->boolean('is_verified')->default(false);

            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('artist_bank_details');
    }
};
