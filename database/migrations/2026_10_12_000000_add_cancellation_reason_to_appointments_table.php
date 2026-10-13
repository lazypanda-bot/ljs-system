<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('appointments', 'cancellation_reason')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->text('cancellation_reason')->nullable()->after('appointment_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('appointments', 'cancellation_reason')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->dropColumn('cancellation_reason');
            });
        }
    }
};
