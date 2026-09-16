<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eén "partnerkoppeling" per uitnodiging (zie het implementatieplan, sectie "Datamodel"). De
 * snapshots bevriezen het resultaat van elke deelnemer op het moment dat die klaar is — een latere
 * wijziging aan StyleProfile/AccentColor/BasePalette mag een al getoonde vergelijking nooit met
 * terugwerkende kracht veranderen, exact dezelfde denormalisatieredenering als
 * QuizResult::chosen_accent_colors/chosen_base_palette.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('initiator_quiz_result_id')->constrained('quiz_results');
            $table->json('initiator_snapshot');
            $table->foreignId('partner_quiz_result_id')->nullable()->constrained('quiz_results');
            $table->json('partner_snapshot')->nullable();
            $table->string('status')->default('waiting');
            $table->string('invite_token_hash')->unique();
            $table->text('invite_token_encrypted');
            $table->timestamp('invite_expires_at');
            $table->string('initiator_name')->nullable();
            $table->string('partner_name')->nullable();
            $table->timestamp('share_confirmed_at')->nullable();
            $table->string('share_confirmation_text_version')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_links');
    }
};
