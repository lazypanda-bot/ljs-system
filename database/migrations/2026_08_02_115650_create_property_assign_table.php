<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_assign', function (Blueprint $table) {
            $table->id('assign_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->foreignId('property_id')->constrained('property_listings', 'property_id')->onDelete('cascade');
            $table->date('assigned_date');
            $table->unique(['user_id', 'property_id']);
            // $table->timestamp('update_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_assign');
    }
};