<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_listings', function (Blueprint $table) {
            $table->id('property_id');
            $table->foreignId('user_id')->constrained('users', 'user_id');
            $table->string('referral_code')->nullable();
            $table->string('property_name');
            $table->text('property_description')->nullable();
            $table->string('property_type', 100);
            $table->text('property_location');
            $table->integer('price');
            $table->enum('property_status', ['Available', 'Reserved', 'Sold', 'Rented', 'Unavailable'])->default('Available');
            $table->enum('approval_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->timestamp('update_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_listings');
    }
};