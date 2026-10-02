<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('conversation_participants', 'hidden_at')) {
            Schema::table('conversation_participants', function (Blueprint $table) {
                $table->timestamp('hidden_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('conversation_participants', 'hidden_at')) {
            Schema::table('conversation_participants', fn (Blueprint $table) => $table->dropColumn('hidden_at'));
        }
    }
};