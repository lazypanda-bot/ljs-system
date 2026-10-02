<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('property_listings')
            ->where('listing_mode', 'admin')
            ->where('approval_status', 'Approved')
            ->whereNull('deleted_at')
            ->orderBy('property_id')
            ->get(['property_id', 'user_id'])
            ->each(function ($property): void {
                $exists = DB::table('referral_codes')
                    ->where('property_id', $property->property_id)
                    ->where('user_id', $property->user_id)
                    ->exists();
                if ($exists) {
                    return;
                }

                do {
                    $code = 'LJS-' . strtoupper(Str::random(10));
                } while (DB::table('referral_codes')->where('code', $code)->exists());

                $referralData = [
                    'property_id' => $property->property_id,
                    'user_id' => $property->user_id,
                    'code' => $code,
                ];
                if (Schema::hasColumn('referral_codes', 'created_at')) {
                    $referralData['created_at'] = now();
                }
                if (Schema::hasColumn('referral_codes', 'updated_at')) {
                    $referralData['updated_at'] = now();
                }

                DB::table('referral_codes')->insert($referralData);
            });
    }

    public function down(): void
    {
        // Keep referral codes intact when rolling back; they may already be shared.
    }
};