<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('posted_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_open')->default(false);
            $table->enum('approval_status', ['approved', 'pending', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_duplicate_flagged')->default(false);
            $table->foreignId('duplicate_of')->nullable()->constrained('listings')->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 15, 2);
            $table->string('location');
            $table->enum('location_type', ['city', 'suburban', 'provincial']);
            $table->enum('property_type', ['condominium', 'house_and_lot', 'vacant_lot', 'commercial', 'farmland']);
            $table->string('developer')->nullable();
            $table->boolean('has_security')->default(false);
            $table->boolean('near_schools')->default(false);
            $table->decimal('lot_area', 10, 2)->nullable();
            $table->enum('status', ['available', 'reserved', 'sold'])->default('available');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};