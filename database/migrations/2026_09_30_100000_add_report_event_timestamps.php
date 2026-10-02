<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_listings', function (Blueprint $table) {
            $table->timestamp('sold_at')->nullable()->after('property_status');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('appointment_status');
        });

        Schema::create('property_inquiries', function (Blueprint $table) {
            $table->id('inquiry_id');
            $table->foreignId('property_id')->constrained('property_listings', 'property_id')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('conversations', 'conversation_id')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['property_id', 'conversation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_inquiries');
        Schema::table('appointments', fn (Blueprint $table) => $table->dropColumn('completed_at'));
        Schema::table('property_listings', fn (Blueprint $table) => $table->dropColumn('sold_at'));
    }
};