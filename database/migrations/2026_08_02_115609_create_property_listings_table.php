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
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade'); //change to assign_id
            // $table->string('referral_code')->nullable();
            $table->string('referral_code')->nullable()->unique();
            $table->string('property_name');
            $table->text('property_description')->nullable();
            $table->string('property_type', 100);
            // $table->enum('property_type', ['House', 'Condo', 'Townhouse', 'Lot'])->default('House');
            // $table->text('property_location');
            $table->string('house_number')->nullable(); 
            $table->string('street');                   
            $table->string('city');                  
            $table->string('province');
            $table->string('lot_number')->nullable(); 

            // $table->integer('price');
            $table->decimal('price', 12, 2);
            // $table->enum('property_status', ['For Sale', 'Reserved', 'Sold', 'For Rent', 'Unavailable']);
            $table->string('property_status')->default('Available');
            // $table->boolean('rush')->default(false);
            $table->string('approval_status')->default('Approved');
            $table->boolean('featured')->default(false);
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