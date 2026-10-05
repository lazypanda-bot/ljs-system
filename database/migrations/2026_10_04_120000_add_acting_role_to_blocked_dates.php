<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('blocked_dates', 'acting_role')) {
            Schema::table('blocked_dates', function (Blueprint $table): void {
                $table->string('acting_role', 20)->nullable()->after('user_id');
            });
        }

        DB::table('blocked_dates')
            ->whereIn('user_id', DB::table('users')->where('role_name', 'Lead Broker')->select('user_id'))
            ->whereNull('acting_role')
            ->update(['acting_role' => 'admin']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('blocked_dates', 'acting_role')) {
            Schema::table('blocked_dates', function (Blueprint $table): void {
                $table->dropColumn('acting_role');
            });
        }
    }
};
