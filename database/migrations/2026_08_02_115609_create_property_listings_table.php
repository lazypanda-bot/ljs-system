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
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->string('referral_code')->nullable();
            $table->string('property_name');
            $table->text('property_description')->nullable();
            $table->string('property_type', 100);
            $table->text('property_location');
            $table->integer('price');
            $table->enum('property_status', ['For Sale', 'Reserved', 'Sold', 'For Rent', 'Unavailable']);
            $table->enum('approval_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            // $table->timestamp('update_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_listings');
    }
};