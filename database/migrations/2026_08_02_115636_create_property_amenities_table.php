<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_amenities', function (Blueprint $table) {
            $table->id('amenity_id');
            $table->foreignId('details_id')->constrained('property_details', 'details_id')->onDelete('cascade');
            $table->string('amenity_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_amenities');
    }
};