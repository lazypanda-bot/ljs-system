<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id('review_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->foreignId('reviewer_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->foreignId('reviewed_user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('property_listings', 'property_id')->nullOnDelete();
            $table->integer('rating');
            $table->text('review');
            $table->timestamps();
            // $table->timestamp('updated_at')->nullable();
            // $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};