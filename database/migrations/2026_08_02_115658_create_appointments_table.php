<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id('appointment_id');
            $table->foreignId('assign_id')->nullable()->constrained('property_assign', 'assign_id');
            $table->foreignId('blocked_id')->nullable()->constrained('blocked_dates', 'block_id');
            $table->string('contact_name');
            $table->string('contact_number', 20);
            $table->string('contact_email');
            $table->string('facebook_link')->nullable();
            $table->string('appointment_type', 100);
            $table->date('preferred_date');
            $table->time('preferred_time');
            $table->text('additional_note')->nullable();
            $table->enum('appointment_status', ['Pending', 'Confirmed', 'Completed', 'Cancelled', 'Rejected'])->default('Pending');
            $table->timestamp('update_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};