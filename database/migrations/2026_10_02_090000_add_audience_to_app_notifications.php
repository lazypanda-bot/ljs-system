<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('app_notifications', 'audience')) {
            Schema::table('app_notifications', function (Blueprint $table): void {
                // 'personal' = addressed to the user's own work; 'system' = admin oversight feed.
                $table->string('audience', 20)->default('personal')->after('type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('app_notifications', 'audience')) {
            Schema::table('app_notifications', function (Blueprint $table): void {
                $table->dropColumn('audience');
            });
        }
    }
};
