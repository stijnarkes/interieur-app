<?php

namespace Database\Seeders;

use App\Models\SiteContent;
use Illuminate\Database\Seeder;

/**
 * Zet de huidige, letterlijke schermteksten (voorheen hardcoded in welcome.blade.php, de
 * resultaatpagina-JS-componenten en de bevestigingsmail) als beginwaarde neer — na migreren
 * verandert er zichtbaar niets totdat een admin via TekstenPage bewust iets wijzigt.
 */
class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $existing = SiteContent::query()->first();

        if ($existing) {
            return;
        }

        SiteContent::create([
            'start_title' => 'Ontdek jouw woonstijl',
            'start_intro' => 'Kies jouw favorieten en ontdek in een paar minuten welke stijl, kleuren en meubels bij jou passen.',
            'start_button_label' => 'Start de stijlanalyse',
            'start_duration_fact' => '± 3 minuten',
            'start_questions_suffix' => 'vragen',

            'result_page_title' => 'Jouw persoonlijke woonstijl',
            'hero_eyebrow' => 'Jouw persoonlijke woonstijl',
            'hero_expectation' => 'Dit is een eerste richting op basis van wat jij mooi vindt. Onze interieurstylistes helpen je graag om deze stijl te vertalen naar jouw eigen woning.',
            'hero_primary_label' => 'Basisstijl',
            'hero_secondary_label' => 'Invloed',

            'teaser_title' => 'Jouw persoonlijke interieuradvies staat klaar',
            'teaser_intro' => 'Op basis van al jouw keuzes hebben we een persoonlijk woonstijlrapport voor je samengesteld.',
            'teaser_list_intro' => 'In jouw rapport vind je onder andere:',
            'teaser_checklist_items' => [
                'Jouw persoonlijke kleurenpalet',
                'Materialen die goed bij jouw stijl passen',
                'Advies voor meubels, vormen en stoffen',
                'Jouw persoonlijke moodboard',
                'Jouw interieurrecept',
                'Tips over wat juist minder goed bij jouw stijl past',
            ],
            'teaser_mock_label' => 'Jouw woonstijlrapport',

            'lead_heading' => 'Ontvang jouw persoonlijke woonstijlrapport',
            'lead_intro' => 'Vul hieronder je gegevens in en ontvang jouw complete persoonlijke interieuradvies als PDF in je mailbox.',
            'lead_optin_label' => 'Ik ontvang graag af en toe wooninspiratie, tips en acties van Boer Staphorst.',
            'lead_submit_label' => 'Stuur mijn woonstijlrapport',
            'lead_reassurance' => 'Je ontvangt jouw rapport direct per e-mail. Geen verplichtingen.',

            'lead_success_title' => 'Je woonstijlrapport is verzonden',
            'lead_success_body' => 'Bedankt, {name}. We hebben jouw persoonlijke woonstijlrapport verstuurd naar {email}.',
            'lead_spam_hint' => 'Nog geen e-mail? Kijk voor de zekerheid even in je spam.',
            'lead_expect_title' => 'Wat kun je verwachten?',
            'lead_expect_items' => [
                'Jouw persoonlijke woonstijl',
                'Kleuren, materialen en vormen die bij je passen',
                'Een persoonlijk moodboard en interieuradvies',
            ],
            'lead_resend_label' => 'Opnieuw versturen',

            'email_subject' => 'Jouw Woonstijl | Boer Staphorst',
            'email_header' => 'Jouw persoonlijke woonstijl',
            'email_greeting' => 'Hoi',
            'email_intro' => 'Bedankt voor het doen van de interieurstijltest van Boer Staphorst. Jouw woonstijl:',
            'email_outro' => 'De volledige uitslag met jouw moodboard vind je in de bijgevoegde PDF.',
            'email_cta_label' => 'Plan een interieuradvies',
            'email_cta_url' => 'https://www.boer-staphorst.nl/wonen/interieuradvies',
        ]);
    }
}
