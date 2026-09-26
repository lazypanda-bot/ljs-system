<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_listings', function (Blueprint $table) {
            $table->string('listing_mode', 20)->default('professional')->after('approval_status');
        });

        $adminIds = DB::table('users')->where('role_name', 'Admin')->pluck('user_id');
        DB::table('property_listings')->whereIn('user_id', $adminIds)->update(['listing_mode' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('property_listings', function (Blueprint $table) {
            $table->dropColumn('listing_mode');
        });
    }
};