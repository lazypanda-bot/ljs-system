<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lifestyle_result_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->constrained('lifestyle_results')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('lifestyle_questions')->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('lifestyle_options')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lifestyle_result_answers');
    }
};