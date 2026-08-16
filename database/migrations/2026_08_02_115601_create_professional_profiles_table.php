<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_profiles', function (Blueprint $table) {
            $table->id('profile_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->string('license_number', 100)->nullable();
            $table->string('document_file', 255)->nullable();
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->text('professional_bio')->nullable();
            // $table->timestamp('update_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_profiles');
    }
};