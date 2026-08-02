<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_details', function (Blueprint $table) {
            $table->id('details_id');
            $table->foreignId('property_id')->constrained('property_listings', 'property_id')->onDelete('cascade');
            $table->decimal('lot_area', 10, 2)->nullable();
            $table->decimal('floor_area', 10, 2)->nullable();
            $table->integer('bedroom')->default(0);
            $table->integer('bathroom')->default(0);
            $table->integer('parking_spaces')->default(0);
            $table->integer('number_of_floors')->default(1);
            $table->string('property_condition', 100)->nullable();
            $table->year('year_built')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('update_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_details');
    }
};