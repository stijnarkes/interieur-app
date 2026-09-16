<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eén-rij-tabel (zelfde patroon als QuizSetting) met de stijl-onafhankelijke schermteksten van
 * het startscherm, de resultatenpagina en de bevestigingsmail — admin-beheerbaar via TekstenPage.
 * Stijlspecifieke teksten (introductie, kenmerken, kleuren, materialen, advies) staan op
 * StyleProfile, niet hier.
 */
class SiteContent extends Model
{
    protected $fillable = [
        'start_title',
        'start_intro',
        'start_button_label',
        'start_duration_fact',
        'start_questions_suffix',

        'result_page_title',
        'hero_eyebrow',
        'hero_expectation',
        'hero_primary_label',
        'hero_secondary_label',

        'teaser_title',
        'teaser_intro',
        'teaser_list_intro',
        'teaser_checklist_items',
        'teaser_mock_label',

        'lead_heading',
        'lead_intro',
        'lead_optin_label',
        'lead_submit_label',
        'lead_reassurance',

        'lead_success_title',
        'lead_success_body',
        'lead_queued_title',
        'lead_queued_body',
        'lead_spam_hint',
        'lead_expect_title',
        'lead_expect_items',
        'lead_resend_label',

        'email_subject',
        'email_header',
        'email_greeting',
        'email_intro',
        'email_outro',
        'email_cta_label',
        'email_cta_url',

        'accent_step_title',
        'accent_step_intro',
        'accent_step_hint',
        'accent_step_continue_label',
        'accent_step_chosen_title',
        'accent_step_change_label',
        'accent_step_error_message',
    ];

    protected $casts = [
        'teaser_checklist_items' => 'array',
        'lead_expect_items' => 'array',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
