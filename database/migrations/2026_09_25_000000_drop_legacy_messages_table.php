<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('messages');
    }

    public function down(): void
    {
        Schema::create('messages', function ($table) {
            $table->id('message_id');
            $table->foreignId('sender_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->enum('message_type', ['Inbox', 'Sent'])->default('Inbox');
            $table->string('attachment')->nullable();
            $table->string('sender_type', 100)->nullable();
            $table->string('receiver_type', 100)->nullable();
            $table->timestamps();
        });
    }
};