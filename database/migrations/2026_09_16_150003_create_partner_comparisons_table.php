<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Eén actieve vergelijking per partner_link — zie App\Services\PartnerComparisonService. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_link_id')->constrained()->cascadeOnDelete();
            $table->string('algorithm_version');
            $table->string('content_version')->nullable();
            $table->json('facts')->nullable();
            $table->json('suggestions')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_comparisons');
    }
};
