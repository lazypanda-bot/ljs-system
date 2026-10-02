<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('app_notifications', 'appointment_id')) {
            Schema::table('app_notifications', function (Blueprint $table): void {
                $table->foreignId('appointment_id')
                    ->nullable()
                    ->after('appointment_proposal_id')
                    ->constrained('appointments', 'appointment_id')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('app_notifications', 'appointment_id')) {
            Schema::table('app_notifications', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('appointment_id');
            });
        }
    }
};