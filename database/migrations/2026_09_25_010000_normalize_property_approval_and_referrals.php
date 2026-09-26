<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        DB::table('property_listings')->where('approval_status', 'Live')->update(['approval_status' => 'Approved']);

        if (Schema::hasColumn('property_listings', 'referral_code')) {
            Schema::table('property_listings', function (Blueprint $table) {
                $table->dropUnique('property_listings_referral_code_unique');
                $table->dropColumn('referral_code');
            });
        }

        if (! $isSqlite) {
            Schema::table('property_listings', function (Blueprint $table) {
                $table->enum('property_status', ['Available', 'For Sale', 'For Rent', 'Reserved', 'Sold', 'Unavailable', 'Pending'])->default('Available')->change();
                $table->enum('approval_status', ['Pending', 'Approved', 'Rejected'])->default('Approved')->change();
            });
        }

        if (Schema::hasTable('referral_code') && ! Schema::hasTable('referral_codes')) {
            Schema::rename('referral_code', 'referral_codes');
        }

        if ($isSqlite) {
            $this->rebuildSqliteReferralCodes();
            return;
        }

        DB::statement('ALTER TABLE referral_codes MODIFY referral_code_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        if (! Schema::hasColumn('referral_codes', 'code')) {
            Schema::table('referral_codes', function (Blueprint $table) {
                $table->string('code', 255)->nullable()->after('referral_code_id');
            });
        }

        if (! Schema::hasColumn('referral_codes', 'updated_at')) {
            Schema::table('referral_codes', function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable();
            });
        }

        DB::table('referral_codes')->orderBy('referral_code_id')->each(function ($referral) {
            DB::table('referral_codes')->where('referral_code_id', $referral->referral_code_id)->update([
                'code' => 'LJS-' . strtoupper(substr(hash('sha256', $referral->referral_code_id . $referral->property_id . $referral->user_id), 0, 10)),
            ]);
        });

        Schema::table('referral_codes', function (Blueprint $table) {
            $table->string('code', 255)->nullable(false)->change();
            $table->unique('code');
            $table->unique(['property_id', 'user_id']);
        });
    }

    private function rebuildSqliteReferralCodes(): void
    {
        if (! Schema::hasTable('referral_codes')) {
            Schema::create('referral_codes', function (Blueprint $table) {
                $table->id('referral_code_id');
                $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
                $table->foreignId('property_id')->constrained('property_listings', 'property_id')->cascadeOnDelete();
                $table->string('code')->unique();
                $table->timestamps();
                $table->unique(['property_id', 'user_id']);
            });

            return;
        }

        $legacyTable = 'referral_codes_legacy';
        if (Schema::hasTable($legacyTable)) {
            Schema::drop($legacyTable);
        }
        Schema::rename('referral_codes', $legacyTable);

        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id('referral_code_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('property_listings', 'property_id')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->timestamps();
            $table->unique(['property_id', 'user_id']);
        });

        $seenPairs = [];
        $usedCodes = [];
        foreach (DB::table($legacyTable)->orderBy('referral_code_id')->get() as $referral) {
            $pair = $referral->property_id . ':' . $referral->user_id;
            if (isset($seenPairs[$pair])) {
                continue;
            }
            $seenPairs[$pair] = true;

            $code = isset($referral->code) && trim((string) $referral->code) !== ''
                ? (string) $referral->code
                : 'LJS-' . strtoupper(substr(hash('sha256', $referral->referral_code_id . $pair), 0, 10));
            while (isset($usedCodes[$code])) {
                $code = 'LJS-' . strtoupper(substr(hash('sha256', $code . $pair), 0, 10));
            }
            $usedCodes[$code] = true;

            $createdAt = $referral->created_at ?? now();
            DB::table('referral_codes')->insert([
                'user_id' => $referral->user_id,
                'property_id' => $referral->property_id,
                'code' => $code,
                'created_at' => $createdAt,
                'updated_at' => $referral->updated_at ?? $createdAt,
            ]);
        }

        Schema::drop($legacyTable);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::create('referral_code', function (Blueprint $table) {
                $table->string('referral_code_id', 255)->primary();
                $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
                $table->foreignId('property_id')->constrained('property_listings', 'property_id')->cascadeOnDelete();
                $table->timestamp('created_at')->nullable();
            });

            foreach (DB::table('referral_codes')->orderBy('referral_code_id')->get() as $referral) {
                DB::table('referral_code')->insert([
                    'referral_code_id' => (string) $referral->referral_code_id,
                    'user_id' => $referral->user_id,
                    'property_id' => $referral->property_id,
                    'created_at' => $referral->created_at,
                ]);
            }
            Schema::drop('referral_codes');

            Schema::table('property_listings', function (Blueprint $table) {
                $table->string('referral_code')->nullable()->unique();
            });
            DB::table('property_listings')->where('approval_status', 'Approved')->update(['approval_status' => 'Live']);

            return;
        }

        Schema::table('referral_codes', function (Blueprint $table) {
            $table->dropUnique(['property_id', 'user_id']);
            $table->dropUnique(['code']);
            if (Schema::hasColumn('referral_codes', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
            $table->dropPrimary('referral_code_id');
            $table->string('referral_code_id', 255)->change();
            $table->dropColumn('code');
            $table->primary('referral_code_id');
        });

        Schema::rename('referral_codes', 'referral_code');

        Schema::table('property_listings', function (Blueprint $table) {
            $table->string('referral_code')->nullable()->unique();
            $table->string('property_status')->default('Available')->change();
            $table->string('approval_status')->default('Live')->change();
        });

        DB::table('property_listings')->where('approval_status', 'Approved')->update(['approval_status' => 'Live']);
    }
};
