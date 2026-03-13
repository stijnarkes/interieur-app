<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        set_time_limit(180);

        $request->validate([
            'style'         => 'required|string',
            'moodWords'     => 'nullable|string',
            'colors'        => 'nullable|string',
            'name'          => 'nullable|string|max:255',
            'email'         => 'nullable|email|max:255',
            'marketingOptIn' => 'nullable|boolean',
            'imageBase64'   => 'nullable|string',
        ]);

        $style       = $request->input('style');
        $moodWords   = $request->input('moodWords') ?? '';
        $colors      = $request->input('colors') ?? '';
        $imageBase64 = $request->input('imageBase64') ?? '';

        $apiKey     = config('services.openai.key');
        $model      = config('services.openai.model');
        $imageModel = config('services.openai.image_model');

        // ─── DB: submission aanmaken ──────────────────────────────────────────────
        $submission = Submission::create([
            'style'        => $style,
            'mood_words'   => $moodWords ?: null,
            'colors'       => $colors ?: null,
            'name'         => $request->input('name') ?: null,
            'email'        => $request->input('email') ?: null,
            'email_opt_in' => $request->boolean('marketingOptIn', false),
        ]);

        if ($imageBase64) {
            $submissionId = $submission->id;
            $path = "submissions/{$submissionId}/room_photo.jpg";
            Storage::disk('public')->put($path, base64_decode($imageBase64));
            $submission->update([
                'has_room_photo'  => true,
                'room_photo_path' => $path,
            ]);
        }

        // ─── Stap 1: tekstadvies via GPT-4o ──────────────────────────────────────
        $userMessage  = $this->buildUserMessage($style, $moodWords, $colors, $imageBase64);
        $textResponse = Http::withToken($apiKey)
            ->timeout(120)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'    => $model,
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => <<<'SYS'
Je bent een professionele interieurstyliste met diepgaande kennis van interieurstijlen, materialen en kleurgebruik.

Je taak is om interieuradvies te geven dat logisch voortkomt uit de gekozen woonstijl van de gebruiker.

BELANGRIJK: redeneer eerst intern voordat je het advies schrijft.

Volg altijd deze interne stappen:
1. Bepaal eerst welke materialen typisch zijn voor de gekozen interieurstijl.
2. Bepaal daarna welke kleuren logisch passen bij die stijl.
3. Bepaal vervolgens welke meubels en accessoires daarbij aansluiten.
4. Schrijf daarna het advies.

Het advies moet dus altijd ontstaan vanuit de stijl, niet vanuit voorbeelden in de prompt.

Onderstaande stijl → materiaal logica is alleen ter referentie voor jouw redenering — kopieer ze niet automatisch:
- Scandinavisch: licht eikenhout, wol, linnen, keramiek, matte verf
- Japandi: eikenhout, bamboe, linnen, papierlampen, keramiek
- Industrieel: staal, beton, leer, ruw hout
- Hotel chic: velours, marmer, messing, donker hout
- Landelijk: grenenhout, vlas, terracotta, riet, keramiek
- Modern: gelakt MDF, glas, RVS, betonlook, microvezel

Gebruik concrete materialen: specifieke houtsoorten, stofsoorten, metaalsoorten, steensoorten en afwerkingen.
Geef NOOIT exacte maten of afmetingen. Gebruik relatieve termen: compact, groot, laag, ruim, diep, slank, oversized.
Zorg dat kleuren, materialen en productideeën samen één samenhangend interieurconcept vormen.
Noem nooit vage termen als "mooie neutrale tinten" zonder ze te concretiseren.
Schrijf warm, persoonlijk en inspirerend — als een styliste die persoonlijk advies geeft, niet als een productspecificatie.
Retourneer ALLEEN geldig JSON zonder markdown of codeblokken.
SYS,
                    ],
                    $userMessage,
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature'     => 0.7,
            ]);

        if ($textResponse->failed()) {
            return response()->json([
                'error' => 'Tekstgeneratie mislukt: ' . $textResponse->json('error.message', 'Onbekende fout'),
            ], 500);
        }

        $advice = json_decode($textResponse->json('choices.0.message.content'), true);

        if (! $advice) {
            return response()->json(['error' => 'Ongeldig antwoord van AI ontvangen.'], 500);
        }

        // ─── DB: advies opslaan ───────────────────────────────────────────────────
        $submission->update([
            'advice_bullets' => $advice['adviceBullets'] ?? null,
            'palette'        => $advice['palette'] ?? null,
            'materials'      => $advice['materials'] ?? null,
            'layout_tips'    => $advice['layoutTips'] ?? null,
            'product_ideas'  => $advice['productIdeas'] ?? null,
        ]);

        // ─── Stap 2: moodboard collage via gpt-image-1 (text-to-image) ───────────
        // Genereert altijd een flat-lay design board — nooit een kamerrender.
        $moodboardPrompt = $this->buildMoodboardImagePrompt($style, $moodWords, $advice);
        $moodboardUrl    = $this->generateTextToImage($apiKey, $imageModel, $moodboardPrompt);

        // ─── Stap 3: inspiratiebeeld via gpt-image-1 ─────────────────────────────
        // Met uploadfoto → image-to-image: originele kamer als basis, restyling in gekozen stijl.
        // Zonder foto     → text-to-image fallback.
        $roomDescription   = $imageBase64 ? $this->analyzeRoomImage($apiKey, $model, $imageBase64) : '';
        $inspirationPrompt = $this->buildInspirationImagePrompt($style, $colors, $advice, $roomDescription);

        $roomPreviewUrl = $imageBase64
            ? $this->generateImageToImage($apiKey, $imageModel, $inspirationPrompt, $imageBase64)
            : $this->generateTextToImage($apiKey, $imageModel, $inspirationPrompt);

        // ─── Resultaat samenstellen en opslaan voor e-mail ────────────────────────
        $resultId = Str::uuid()->toString();

        // ─── Afbeeldingen opslaan op schijf ──────────────────────────────────────
        $moodboardPath   = null;
        $inspirationPath = null;

        if ($moodboardUrl && str_starts_with($moodboardUrl, 'data:')) {
            $moodboardBase64 = substr($moodboardUrl, strpos($moodboardUrl, ',') + 1);
            $moodboardPath   = "submissions/{$submission->id}/moodboard.png";
            Storage::disk('public')->put($moodboardPath, base64_decode($moodboardBase64));
        }

        if ($roomPreviewUrl && str_starts_with($roomPreviewUrl, 'data:')) {
            $inspirationBase64 = substr($roomPreviewUrl, strpos($roomPreviewUrl, ',') + 1);
            $inspirationPath   = "submissions/{$submission->id}/inspiration.png";
            Storage::disk('public')->put($inspirationPath, base64_decode($inspirationBase64));
        }

        // ─── DB: resultaat afronden ───────────────────────────────────────────────
        $submission->update([
            'result_id'              => $resultId,
            'result_generated'       => true,
            'moodboard_generated'    => $moodboardUrl !== null,
            'room_preview_generated' => $roomPreviewUrl !== null,
            'moodboard_path'         => $moodboardPath,
            'inspiration_path'       => $inspirationPath,
        ]);

        // ─── PDF genereren en e-mail versturen ───────────────────────────────────
        $emailSent  = false;
        $emailError = null;

        if ($submission->email) {
            try {
                $pdfService = new \App\Services\AdvicePdfService();
                $pdfPath    = $pdfService->generate($submission, $moodboardUrl ?? '', $roomPreviewUrl ?? '');

                $pdfRelativePath = "submissions/{$submission->id}/advice.pdf";
                $submission->update(['pdf_path' => $pdfRelativePath]);

                \Illuminate\Support\Facades\Mail::to($submission->email)->send(
                    new \App\Mail\AdviceMail($submission, $pdfPath)
                );

                $submission->update([
                    'email_status'   => 'sent',
                    'email_sent_at'  => now(),
                ]);
                $emailSent = true;
            } catch (\Throwable $e) {
                $submission->update([
                    'email_status' => 'failed',
                    'email_error'  => $e->getMessage(),
                ]);
                $emailError = 'E-mail versturen mislukt.';
            }
        }

        $result = array_merge($advice, [
            'resultId'               => $resultId,
            'styleLabel'             => $style,
            'moodboardImageDataUrl'  => $moodboardUrl,
            'roomPreviewImageDataUrl' => $roomPreviewUrl,
            'emailSent'              => $emailSent,
            'emailError'             => $emailError,
        ]);

        Cache::put("result:{$resultId}", $result, now()->addHours(24));

        return response()->json($result);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // GPT-4o gebruikersbericht voor tekstadvies: met of zonder uploadfoto
    // ─────────────────────────────────────────────────────────────────────────────

    private function buildUserMessage(
        string $style,
        string $moodWords,
        string $colors,
        string $imageBase64
    ): array {
        $text  = "Geef gedetailleerd interieuradvies voor een **{$style}** woonkamer.\n";
        $text .= "Baseer het advies op de volgende klantinformatie:\n";

        if ($moodWords)   $text .= "- Gewenste sfeer: {$moodWords}\n";
        if ($colors)      $text .= "- Voorkeurskleuren: {$colors}\n";
        if ($imageBase64) $text .= "- Er is een foto van de huidige woonkamer bijgevoegd. Analyseer de ruimte en stem het advies af op wat je ziet.\n";

        $text .= <<<'EOT'

Eisen voor het advies:
- Redeneer vanuit de gekozen stijl. Kies materialen en producten omdat ze bij de stijl passen, niet omdat ze als voorbeeld in de instructie staan.
- Alle kleuren moeten echte, specifieke kleurnamen hebben plus een exacte HEX-waarde.
- Materialen moeten concreet zijn: noem de houtsoort, stofsoort, metaalsoort of afwerking.
- Gebruik geen exacte maten. Beschrijf proporties met relatieve termen: compact, groot, laag, ruim, oversized.
- Zorg dat kleuren, materialen en productideeën samen één samenhangend concept vormen.
- Indelingstips moeten praktisch en ruimtespecifiek zijn.

Retourneer ALLEEN dit JSON-formaat, geen andere tekst:
{
  "adviceBullets": [
    "warm en persoonlijk stijladvies punt 1",
    "warm en persoonlijk stijladvies punt 2",
    "warm en persoonlijk stijladvies punt 3",
    "warm en persoonlijk stijladvies punt 4",
    "warm en persoonlijk stijladvies punt 5"
  ],
  "palette": [
    { "name": "kleurnaam die logisch past bij deze stijl", "hex": "#xxxxxx" },
    { "name": "kleurnaam die logisch past bij deze stijl", "hex": "#xxxxxx" },
    { "name": "kleurnaam die logisch past bij deze stijl", "hex": "#xxxxxx" },
    { "name": "kleurnaam die logisch past bij deze stijl", "hex": "#xxxxxx" },
    { "name": "kleurnaam die logisch past bij deze stijl", "hex": "#xxxxxx" }
  ],
  "materials": [
    {
      "category": "Vloer",
      "recommendations": ["materiaalkeuze die logisch past bij deze stijl", "tweede materiaalkeuze die logisch past bij deze stijl"],
      "do": ["concreet aanrader met uitleg"],
      "dont": ["concreet punt om te vermijden met uitleg"]
    },
    {
      "category": "Meubilair",
      "recommendations": ["materiaalkeuze die logisch past bij deze stijl", "tweede materiaalkeuze die logisch past bij deze stijl"],
      "do": ["concreet aanrader"],
      "dont": ["concreet punt om te vermijden"]
    },
    {
      "category": "Textiel & Accessoires",
      "recommendations": ["materiaalkeuze die logisch past bij deze stijl", "tweede materiaalkeuze die logisch past bij deze stijl"],
      "do": ["concreet aanrader"],
      "dont": ["concreet punt om te vermijden"]
    }
  ],
  "layoutTips": [
    "praktische indelingstip die past bij de ruimte en stijl",
    "praktische indelingstip die past bij de ruimte en stijl",
    "praktische indelingstip die past bij de ruimte en stijl",
    "praktische indelingstip die past bij de ruimte en stijl"
  ],
  "productIdeas": [
    {
      "category": "Bank",
      "exampleSpecs": "beschrijf een bankvorm die logisch past bij deze stijl",
      "material": "concreet materiaal dat logisch past bij deze stijl",
      "colorHint": "kleurrichting die logisch past bij deze stijl"
    },
    {
      "category": "Salontafel",
      "exampleSpecs": "beschrijf een tafelvorm die logisch past bij deze stijl",
      "material": "concreet materiaal dat logisch past bij deze stijl",
      "colorHint": "kleurrichting die logisch past bij deze stijl"
    },
    {
      "category": "Verlichting",
      "exampleSpecs": "beschrijf een lichtarmatuur dat logisch past bij deze stijl",
      "material": "concreet materiaal dat logisch past bij deze stijl",
      "colorHint": "kleurrichting die logisch past bij deze stijl"
    },
    {
      "category": "Wanddecoratie",
      "exampleSpecs": "beschrijf wanddecoratie die logisch past bij deze stijl",
      "material": "concreet materiaal dat logisch past bij deze stijl",
      "colorHint": "kleurrichting die logisch past bij deze stijl"
    }
  ]
}
EOT;

        if (! $imageBase64) {
            return ['role' => 'user', 'content' => $text];
        }

        return [
            'role'    => 'user',
            'content' => [
                ['type' => 'text', 'text' => $text],
                [
                    'type'      => 'image_url',
                    'image_url' => ['url' => 'data:image/jpeg;base64,' . $imageBase64],
                ],
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // MOODBOARD IMAGE PROMPT
    // Genereert altijd een flat-lay collage — nooit een kamerrender.
    // Gebruikt de werkelijke palette-kleuren, materialen en producten uit het advies.
    // ─────────────────────────────────────────────────────────────────────────────

    private function buildMoodboardImagePrompt(string $style, string $moodWords, array $advice): string
    {
        // Palette: kleurnamen + hex
        $paletteColors = [];
        if (! empty($advice['palette'])) {
            foreach (array_slice($advice['palette'], 0, 5) as $c) {
                $paletteColors[] = "{$c['name']} {$c['hex']}";
            }
        }
        $paletteStr = implode(', ', $paletteColors);

        // Materialen: eerste aanbeveling per categorie
        $materialItems = [];
        if (! empty($advice['materials'])) {
            foreach ($advice['materials'] as $mat) {
                $rec = $mat['recommendations'][0] ?? null;
                if ($rec) $materialItems[] = $rec;
            }
        }
        $materialsStr = implode(', ', $materialItems);

        // Producten: category + materiaal
        $productItems = [];
        if (! empty($advice['productIdeas'])) {
            foreach (array_slice($advice['productIdeas'], 0, 4) as $p) {
                $productItems[] = "{$p['category']} in {$p['material']}";
            }
        }
        $productsStr = implode(', ', $productItems);

        // Stricte flat-lay collage prompt — alle room-render taal is bewust weggelaten.
        return implode(' ', array_filter([
            "Flat lay studio photograph of a professional interior design sample board.",
            "Top-down bird's eye view on a plain white surface.",
            "The board contains only these physical objects arranged loosely and slightly overlapping:",
            "five painted color swatch cards in exactly these colors: {$paletteStr};",
            "close-up material texture chips and samples: {$materialsStr};",
            "small product reference cutouts: {$productsStr};",
            "folded fabric swatches, paint chip cards, small ceramic vessels, dried botanical sprigs.",
            $moodWords ? "Aesthetic mood: {$moodWords}." : null,
            "Style: {$style} interior design.",
            "This must look exactly like a physical designer sample board photographed from directly above.",
            "Restrictions: do not draw a room, do not draw walls or floors or ceilings, do not draw a sofa scene, do not draw any interior perspective.",
            "Text restrictions: no text, no numbers, no letters, no labels, no captions, no color codes, no hex codes, no annotations anywhere.",
        ]));
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // INSPIRATION IMAGE PROMPT
    // Genereert een gestyled kamerrender — volledig los van de moodboard prompt.
    // $roomDescription is de architectuuranalyse van de uploadfoto (kan leeg zijn).
    // ─────────────────────────────────────────────────────────────────────────────

    private function buildInspirationImagePrompt(
        string $style,
        string $colors,
        array $advice,
        string $roomDescription = ''
    ): string {
        $paletteStr = '';
        if (! empty($advice['palette'])) {
            $paletteStr = implode(', ', array_map(fn($c) => $c['name'], array_slice($advice['palette'], 0, 5)));
        }

        $materialsStr = '';
        if (! empty($advice['materials'])) {
            $parts = [];
            foreach (array_slice($advice['materials'], 0, 2) as $mat) {
                $rec = $mat['recommendations'][0] ?? '';
                if ($rec) $parts[] = $rec;
            }
            $materialsStr = implode(', ', $parts);
        }

        $shared = implode(' ', array_filter([
            "Interior style: {$style}.",
            $paletteStr   ? "Color palette: {$paletteStr}." : null,
            $materialsStr ? "Key materials: {$materialsStr}." : null,
            "Photorealistic interior photography, natural daylight, architectural quality.",
            "No text, no watermarks, no labels anywhere in the image.",
        ]));

        if (! $roomDescription) {
            // Geen uploadfoto: generiek styled kamerrender
            return "Beautifully redesigned living room interior in {$style} style. {$shared}";
        }

        // Met uploadfoto: restyling van de originele ruimte
        return implode(' ', [
            "Photorealistic interior redesign based on the provided room photo.",
            "Use the uploaded image as the exact spatial reference.",
            "The following architectural characteristics must remain completely unchanged: {$roomDescription}.",
            "Preserve: room layout, window placement and size, door placement, ceiling height, camera perspective, room proportions.",
            "Only restyle these interior elements: furniture, wall colors, flooring material, textiles, lighting, decorative accessories.",
            "Replace all existing furniture with new pieces in {$style} style.",
            "The result must look like the same room redesigned by a professional interior designer.",
            $shared,
            "Do not change architecture. Do not move windows. Do not change perspective. Do not generate a completely new room.",
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // GPT-4o vision: analyseert de uploadfoto en retourneert een beknopte
    // architectuurbeschrijving voor gebruik in de inspiratiebeeld-prompt.
    // ─────────────────────────────────────────────────────────────────────────────

    private function analyzeRoomImage(string $apiKey, string $model, string $imageBase64): string
    {
        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'      => $model,
                'max_tokens' => 150,
                'messages'   => [
                    [
                        'role'    => 'system',
                        'content' => 'You are an architectural analyst. Describe only the fixed structural features of the room in one concise English sentence suitable for an image generation prompt. Include: camera angle and perspective, number and position of windows, ceiling height impression, floor surface, wall layout, and any fixed architectural features (beams, fireplace, built-in alcoves, columns). Do not mention furniture, colors, style, or decorations.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'Describe the fixed architectural structure of this room.'],
                            [
                                'type'      => 'image_url',
                                'image_url' => ['url' => 'data:image/jpeg;base64,' . $imageBase64],
                            ],
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            return 'rectangular living room, eye-level perspective, large window on one wall, standard ceiling height';
        }

        return trim($response->json('choices.0.message.content') ?? 'rectangular living room with natural light');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // gpt-image-1: text-to-image via /v1/images/generations
    // Gebruikt voor het moodboard en als fallback voor het inspiratiebeeld.
    // Respons is altijd b64_json; wordt omgezet naar data-URL.
    // ─────────────────────────────────────────────────────────────────────────────

    private function generateTextToImage(string $apiKey, string $model, string $prompt): ?string
    {
        $response = Http::withToken($apiKey)
            ->timeout(120)
            ->post('https://api.openai.com/v1/images/generations', [
                'model'   => $model,
                'prompt'  => $prompt,
                'n'       => 1,
                'size'    => '1024x1024',
                'quality' => 'high',
            ]);

        if ($response->failed()) {
            return null;
        }

        // gpt-image-1 geeft b64_json terug; DALL-E 3 geeft een URL
        $b64 = $response->json('data.0.b64_json');
        if ($b64) {
            return 'data:image/png;base64,' . $b64;
        }

        return $response->json('data.0.url');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // gpt-image-1: image-to-image via /v1/images/edits
    // Gebruikt voor het inspiratiebeeld wanneer de gebruiker een foto heeft geüpload.
    // De uploadfoto wordt als base image meegegeven zodat de ruimte behouden blijft.
    // ─────────────────────────────────────────────────────────────────────────────

    private function generateImageToImage(
        string $apiKey,
        string $model,
        string $prompt,
        string $imageBase64
    ): ?string {
        $imageData = base64_decode($imageBase64);

        $response = Http::withToken($apiKey)
            ->timeout(120)
            ->attach('image[]', $imageData, 'room.jpg', ['Content-Type' => 'image/jpeg'])
            ->post('https://api.openai.com/v1/images/edits', [
                'model'   => $model,
                'prompt'  => $prompt,
                'n'       => 1,
                'size'    => '1024x1024',
                'quality' => 'high',
            ]);

        if ($response->failed()) {
            // Fallback naar text-to-image als de edit mislukt
            return $this->generateTextToImage($apiKey, $model, $prompt);
        }

        $b64 = $response->json('data.0.b64_json');
        if ($b64) {
            return 'data:image/png;base64,' . $b64;
        }

        return $response->json('data.0.url');
    }
}
