<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache/versionering van AI-gegenereerde (of fallback-)adviesteksten per quizresultaat. De
 * unique-constraint + een firstOrCreate-check vóór elke aanroep is het mechanisme dat voorkomt
 * dat hetzelfde resultaat twee keer gegenereerd wordt. prompt_version/style_content_version maken
 * een eerder gegenereerd rapport reproduceerbaar, ook nadat de prompt of stijlinhoud later wijzigt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_result_id')->constrained('quiz_results')->cascadeOnDelete();
            $table->string('variant');
            $table->string('prompt_version')->nullable();
            $table->string('style_content_version')->nullable();
            $table->json('input_summary')->nullable();
            $table->json('output')->nullable();
            $table->string('status')->default('pending');
            $table->text('error')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['quiz_result_id', 'variant']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
    }
};
