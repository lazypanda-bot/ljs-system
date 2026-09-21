<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_assign', function (Blueprint $table) {
            $table->string('status', 20)->default('Approved')->after('assigned_date');
            $table->foreignId('requested_by')->nullable()->constrained('users', 'user_id')->nullOnDelete()->after('status');
            $table->foreignId('reviewed_by')->nullable()->constrained('users', 'user_id')->nullOnDelete()->after('requested_by');
            $table->timestamp('requested_at')->nullable()->after('reviewed_by');
            $table->timestamp('reviewed_at')->nullable()->after('requested_at');
            $table->index(['property_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('property_assign', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['property_id', 'status']);
            $table->dropColumn(['status', 'requested_by', 'reviewed_by', 'requested_at', 'reviewed_at']);
        });
    }
};