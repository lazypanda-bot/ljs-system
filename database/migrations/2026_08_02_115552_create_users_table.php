<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->foreignId('role_id')->constrained('roles', 'role_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('contact_number', 20)->nullable();
            $table->string('profile_photo')->nullable();
            $table->enum('account_status', ['Active', 'Inactive', 'Suspended'])->default('Active');
            $table->timestamp('update_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};