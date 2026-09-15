<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eén vaste rij (zelfde patroon als quiz_settings/QuizSetting::current()) met de stijl-onafhankelijke
 * schermteksten die tot nu toe hardcoded stonden in welcome.blade.php, de resultaatpagina-JS-
 * componenten en de bevestigingsmail — admin-beheerbaar via de nieuwe "Teksten"-pagina. De
 * stijlspecifieke teksten (introductie, kenmerken, kleuren, materialen, advies) blijven op
 * style_profiles staan, ongewijzigd.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();

            $table->string('start_title')->nullable();
            $table->text('start_intro')->nullable();
            $table->string('start_button_label')->nullable();
            $table->string('start_duration_fact')->nullable();
            $table->string('start_questions_suffix')->nullable();

            $table->string('result_page_title')->nullable();
            $table->string('hero_eyebrow')->nullable();
            $table->text('hero_expectation')->nullable();
            $table->string('hero_primary_label')->nullable();
            $table->string('hero_secondary_label')->nullable();

            $table->string('teaser_title')->nullable();
            $table->text('teaser_intro')->nullable();
            $table->string('teaser_list_intro')->nullable();
            $table->json('teaser_checklist_items')->nullable();
            $table->string('teaser_mock_label')->nullable();

            $table->string('lead_heading')->nullable();
            $table->text('lead_intro')->nullable();
            $table->string('lead_optin_label')->nullable();
            $table->string('lead_submit_label')->nullable();
            $table->string('lead_reassurance')->nullable();

            $table->string('lead_success_title')->nullable();
            $table->text('lead_success_body')->nullable();
            $table->string('lead_spam_hint')->nullable();
            $table->string('lead_expect_title')->nullable();
            $table->json('lead_expect_items')->nullable();
            $table->string('lead_resend_label')->nullable();

            $table->string('email_subject')->nullable();
            $table->string('email_header')->nullable();
            $table->string('email_greeting')->nullable();
            $table->text('email_intro')->nullable();
            $table->text('email_outro')->nullable();
            $table->string('email_cta_label')->nullable();
            $table->string('email_cta_url')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_contents');
    }
};
