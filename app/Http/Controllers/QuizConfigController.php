<?php

namespace App\Http\Controllers;

use App\Models\QuizMaterial;
use App\Models\QuizOption;
use App\Models\QuizTransitionSection;
use App\Models\SiteContent;
use App\Support\QuizImageManifest;
use App\Support\QuizStructure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Publieke, alleen-lezen configuratie voor de klant-quiz: de admin-bewerkbare inhoud van
 * quiz_questions/quiz_options/quiz_materials (vraagvolgorde, titel, stijl, afbeelding,
 * kleurmetadata, actief/inactief, showroomvelden, materialen per stijl). De overige rijke
 * stijlprofielinhoud (traits, meubeladvies, recept, etc.) blijft in de JS-bundel
 * (styleProfiles.js) — alleen dit bewerkbare deel komt hiervandaan. `image` wordt hier altijd
 * meegegeven (ook voor de oorspronkelijke, geseede opties) omdat een door de admin zelf
 * toegevoegde extra optie/materiaal geen tegenhanger in de statische bundel heeft om op terug te
 * vallen — zie resources/js/quiz/remoteConfig.js, dat de vraag-/optielijst volledig vervangt
 * i.p.v. alleen bestaande rijen te overschrijven. Alleen actieve opties worden geretourneerd; dat
 * is het hele deactivatie-mechanisme voor opties. `atmosphere` en `transitionPhotos` geven de
 * echte URL van de sfeer-/overgangsschermfoto's mee, ter vervanging van de vaste paden die anders
 * hardcoded in de JS-bundel zouden staan (zie styleProfiles.js/sectionTransition.js) — nodig
 * zodra die foto's niet meer onder public/ staan.
 */
class QuizConfigController extends Controller
{
    public function show(): JsonResponse
    {
        $questions = collect(QuizStructure::questions())
            ->map(fn (array $question, string $id): array => [
                'id' => $id,
                'section' => $question['section'],
                'title' => $question['title'],
                'maxSelections' => $question['maxSelections'],
                'imageDisplayMode' => $question['imageDisplayMode'],
            ])
            ->values();

        $materials = QuizMaterial::query()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('style_key')
            ->map(fn ($group) => $group->map(fn (QuizMaterial $material): array => [
                'name' => $material->name,
                'image' => $material->publicImageUrl(),
            ])->values());

        $options = QuizOption::query()
            ->where('is_active', true)
            ->get()
            // Een optie zonder gekoppelde stijl is onvolledig (zie QuizOptionsPage) en mag nooit
            // in de klant-quiz verschijnen, ook niet als hij per ongeluk op actief staat — er
            // wordt nooit een stijl verzonnen voor een optie zonder koppeling.
            ->filter(fn (QuizOption $option): bool => $option->linkedStyleKeys() !== [])
            ->map(fn (QuizOption $option): array => [
                'id' => $option->option_slug,
                'questionId' => $option->question_id,
                'title' => $option->title,
                'image' => $option->publicImageUrl(),
                'primaryStyle' => $option->linkedStyleKeys()[0] ?? null,
                'styles' => $option->linkedStyleKeys(),
                'colorHex' => $option->color_hex,
                'colorFamily' => $option->color_family,
                'colorTemperature' => $option->color_temperature,
                'product' => [
                    'name' => $option->product_name,
                    'sku' => $option->sku,
                    'brand' => $option->brand,
                    'url' => $option->product_url,
                    'price' => $option->price,
                    'showroomProduct' => $option->showroom_product,
                ],
            ])
            ->values();

        // Sfeerfoto per stijl (hero op de resultaatpagina) en overgangsschermfoto per sectie
        // staan met een vast pad in de JS-bundel (styleProfiles.js/sectionTransition.js) — die
        // paden kloppen alleen zolang bestanden onder public/ staan. Hier krijgt de frontend de
        // echte, disk-onafhankelijke URL mee zodat remoteConfig.js die kan overschrijven (zie
        // applyTransitionPhotos()), ook wanneer de opslag naar S3 verhuist. publicUrlForPath()
        // controleert hier per pad live of het bestand bestaat (er is geen has_image-achtige
        // kolom voor deze vaste-slot-foto's) — op S3 is dat een netwerkverzoek per pad, dus 5
        // minuten cachen om dat niet op elke paginabezoeker te laten drukken. Deze foto's
        // veranderen toch zelden; een admin ziet een update dan met een kleine vertraging.
        $atmosphere = Cache::remember('quiz-config:atmosphere-urls', 300, fn () => collect(QuizStructure::styleOptions())
            ->mapWithKeys(fn (string $label, string $key): array => [
                $key => QuizImageManifest::publicUrlForPath('images/interior/atmosphere/'.QuizStructure::styleSlug($key).'.webp'),
            ]));

        $transitionPhotos = Cache::remember('quiz-config:transition-photo-urls', 300, fn () => collect(array_keys(QuizStructure::SECTIONS))
            ->mapWithKeys(fn (string $sectionId): array => [
                $sectionId => QuizImageManifest::publicUrlForPath("images/interior/transitions/{$sectionId}.webp"),
            ]));

        // De teksten van de overgangsschermen (title/tagline/wrapUp/cta) — admin-beheerbaar via
        // TekstenPage, zie QuizTransitionSection. Geen cache nodig: platte DB-read, geen
        // disk-bestaanscontroles zoals hierboven.
        $sections = QuizTransitionSection::query()->get()->keyBy('section_id')
            ->map(fn (QuizTransitionSection $section): array => [
                'title' => $section->title,
                'tagline' => $section->tagline,
                'wrapUp' => $section->wrap_up,
                'cta' => $section->cta,
            ]);

        // De stijl-onafhankelijke resultatenpagina-teksten (heldenblok/rapport-teaser/
        // aanvraagformulier/bevestiging) — admin-beheerbaar via TekstenPage, zie SiteContent.
        // Overschrijft de fallback-teksten in resources/js/quiz/copy.js (zie remoteConfig.js's
        // applyCopy()), zelfde techniek als hierboven voor foto's.
        $siteContent = SiteContent::current();
        $copy = [
            'resultHero' => [
                'eyebrow' => $siteContent->hero_eyebrow,
                'expectation' => $siteContent->hero_expectation,
                'primaryLabel' => $siteContent->hero_primary_label,
                'secondaryLabel' => $siteContent->hero_secondary_label,
            ],
            'reportTeaser' => [
                'title' => $siteContent->teaser_title,
                'intro' => $siteContent->teaser_intro,
                'listIntro' => $siteContent->teaser_list_intro,
                'checklistItems' => $siteContent->teaser_checklist_items,
                'mockLabel' => $siteContent->teaser_mock_label,
            ],
            'leadForm' => [
                'heading' => $siteContent->lead_heading,
                'intro' => $siteContent->lead_intro,
                'optInLabel' => $siteContent->lead_optin_label,
                'submitLabel' => $siteContent->lead_submit_label,
                'reassurance' => $siteContent->lead_reassurance,
                'successTitle' => $siteContent->lead_success_title,
                'successBody' => $siteContent->lead_success_body,
                'queuedTitle' => $siteContent->lead_queued_title,
                'queuedBody' => $siteContent->lead_queued_body,
                'spamHint' => $siteContent->lead_spam_hint,
                'expectTitle' => $siteContent->lead_expect_title,
                'expectItems' => $siteContent->lead_expect_items,
                'resendLabel' => $siteContent->lead_resend_label,
            ],
        ];

        return response()->json([
            'questions' => $questions,
            'options' => $options,
            'materials' => $materials,
            'atmosphere' => $atmosphere,
            'transitionPhotos' => $transitionPhotos,
            'sections' => $sections,
            'copy' => $copy,
        ]);
    }
}
