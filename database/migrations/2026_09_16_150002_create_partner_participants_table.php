<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Precies 2 rijen per partner_link (initiator + partner). De unique(['partner_link_id','role'])
 * -constraint IS het atomaire-claim-mechanisme (zie PartnerLinkController::claim()): een tweede,
 * gelijktijdige claimpoging voor dezelfde rol op dezelfde koppeling knalt op deze db-constraint
 * i.p.v. op een applicatieniveau-race-conditie. `quiz_result_id` wordt pas gezet zodra déze
 * deelnemer de (geïsoleerde) individuele test heeft afgerond — zie QuizResultController::store().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_link_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->foreignId('quiz_result_id')->nullable()->constrained('quiz_results');
            $table->string('access_token_hash')->unique();
            $table->string('email')->nullable();
            $table->timestamp('mail_requested_at')->nullable();
            $table->string('mail_status')->nullable();
            $table->text('mail_error')->nullable();
            $table->timestamps();
            $table->unique(['partner_link_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_participants');
    }
};
