<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('property_listings')->where('approval_status', 'Approved')->update(['approval_status' => 'Live']);
        Schema::table('property_listings', function ($table) {
            $table->string('approval_status')->default('Live')->change();
        });
    }

    public function down(): void
    {
        DB::table('property_listings')->where('approval_status', 'Live')->update(['approval_status' => 'Approved']);
        Schema::table('property_listings', function ($table) {
            $table->string('approval_status')->default('Approved')->change();
        });
    }
};
