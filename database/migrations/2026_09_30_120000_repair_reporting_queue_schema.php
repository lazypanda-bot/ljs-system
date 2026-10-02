<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('property_listings', 'sold_at')) {
            Schema::table('property_listings', fn (Blueprint $table) => $table->timestamp('sold_at')->nullable());
        }

        if (! Schema::hasColumn('appointments', 'completed_at')) {
            Schema::table('appointments', fn (Blueprint $table) => $table->timestamp('completed_at')->nullable());
        }

        if (! Schema::hasTable('property_inquiries')) {
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

        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        // This repair migration intentionally leaves shared production data intact.
    }
};