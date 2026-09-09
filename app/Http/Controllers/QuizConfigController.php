<?php

namespace App\Http\Controllers;

use App\Models\QuizMaterial;
use App\Models\QuizOption;
use App\Models\QuizPalette;
use App\Support\QuizImageManifest;
use App\Support\QuizStructure;
use Illuminate\Http\JsonResponse;

/**
 * Publieke, alleen-lezen configuratie voor de klant-quiz: de admin-bewerkbare inhoud van
 * quiz_questions/quiz_options/quiz_palettes/quiz_materials (vraagvolgorde, titel, stijl,
 * afbeelding, kleurmetadata, actief/inactief, showroomvelden, sfeerpaletten, materialen per
 * stijl). De overige rijke stijlprofielinhoud (traits, meubeladvies, recept, etc.) blijft in de
 * JS-bundel (styleProfiles.js) — alleen dit bewerkbare deel komt hiervandaan. `image` wordt hier
 * altijd meegegeven (ook voor de oorspronkelijke, geseede opties) omdat een door de admin zelf
 * toegevoegde extra optie/materiaal geen tegenhanger in de statische bundel heeft om op terug te
 * vallen — zie resources/js/quiz/remoteConfig.js, dat de vraag-/optie-/paletten-/materialenlijst
 * volledig vervangt i.p.v. alleen bestaande rijen te overschrijven. Alleen actieve opties worden
 * geretourneerd; dat is het hele deactivatie-mechanisme voor opties. `atmosphere` en
 * `transitionPhotos` geven de echte URL van de sfeer-/overgangsschermfoto's mee, ter vervanging
 * van de vaste paden die anders hardcoded in de JS-bundel zouden staan (zie styleProfiles.js/
 * sectionTransition.js) — nodig zodra die foto's niet meer onder public/ staan.
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
            ->with('styleLinks')
            ->get()
            ->map(fn (QuizOption $option): array => [
                'id' => $option->option_slug,
                'questionId' => $option->question_id,
                'title' => $option->title,
                'image' => $option->publicImageUrl(),
                'primaryStyle' => $option->primary_style,
                'styles' => $option->styleKeys(),
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
        // applyAtmosphere()/applyTransitionPhotos()), ook wanneer de opslag naar S3 verhuist.
        $atmosphere = collect(QuizStructure::styleOptions())
            ->mapWithKeys(fn (string $label, string $key): array => [
                $key => QuizImageManifest::publicUrlForPath('images/interior/atmosphere/'.QuizStructure::styleSlug($key).'.webp'),
            ]);

        $transitionPhotos = collect(array_keys(QuizStructure::SECTIONS))
            ->mapWithKeys(fn (string $sectionId): array => [
                $sectionId => QuizImageManifest::publicUrlForPath("images/interior/transitions/{$sectionId}.webp"),
            ]);

        $palettes = QuizPalette::query()
            ->with(['colors' => fn ($query) => $query->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (QuizPalette $palette): array => [
                'id' => $palette->palette_key,
                'name' => $palette->name,
                'colors' => $palette->colors->map(fn ($color): array => [
                    'name' => $color->name,
                    'hex' => $color->hex,
                ])->values(),
            ])
            ->values();

        return response()->json([
            'questions' => $questions,
            'options' => $options,
            'palettes' => $palettes,
            'materials' => $materials,
            'atmosphere' => $atmosphere,
            'transitionPhotos' => $transitionPhotos,
        ]);
    }
}
