<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('receiver_id');
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('message_type')->default('text');
            $table->string('attachment')->nullable();
            $table->string('sender_type')->nullable();
            $table->string('receiver_type')->nullable();
            $table->timestamp('update_at')->nullable();
            $table->timestamps();

            $table->foreign('sender_id')->references('user_id')->on('users');
            $table->foreign('receiver_id')->references('user_id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};