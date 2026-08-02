<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', [
                'lead_broker',
                'admin',
                'broker',
                'agent',
                'customer'
            ])->default('customer');
            $table->enum('agent_type', ['company', 'freelance'])
                ->nullable();
            $table->enum('rank', [
                'sales_executive',
                'senior_sales_executive',
                'sales_supervisor',
                'sales_manager'
            ])->nullable();
            $table->unsignedBigInteger('broker_id')->nullable();
            $table->string('referral_code')->unique()->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->foreign('broker_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
