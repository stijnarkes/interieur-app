<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InteriorGenerationService
{
    private string $apiKey;
    private string $model;
    private string $imageModel;

    public function __construct()
    {
        $this->apiKey     = config('services.openai.key');
        $this->model      = config('services.openai.model');
        $this->imageModel = config('services.openai.image_model');
    }

    /**
     * Voert de volledige generatie uit en retourneert het resultaat als array.
     * Gooit een exception als een stap mislukt.
     */
    public function generate(array $input, int $submissionId): array
    {
        $style       = $input['style'];
        $moodWords   = $input['moodWords'] ?? '';
        $colors      = $input['colors'] ?? '';
        $imageBase64 = $input['imageBase64'] ?? '';

        // Als de afbeelding al op schijf staat, laad hem van daar
        if (empty($imageBase64) && ! empty($input['roomPhotoPath'])) {
            $raw         = Storage::disk('public')->get($input['roomPhotoPath']);
            $imageBase64 = $raw ? base64_encode($raw) : '';
        }

        // ── Stap 1: tekstadvies via GPT-4o ────────────────────────────────────────
        $advice = $this->generateTextAdvice($style, $moodWords, $colors, $imageBase64);

        // ── Stap 2: moodboard (altijd text-to-image, nooit een kamerrender) ───────
        $moodboardUrl = $this->generateTextToImage(
            $this->buildMoodboardPrompt($style, $moodWords, $advice)
        );

        // ── Stap 3: inspiratiebeeld (image-to-image als foto beschikbaar) ─────────
        $roomDescription   = $imageBase64 ? $this->analyzeRoom($imageBase64) : '';
        $inspirationPrompt = $this->buildInspirationPrompt($style, $advice, $roomDescription);

        $roomPreviewUrl = $imageBase64
            ? $this->generateImageToImage($inspirationPrompt, $imageBase64)
            : $this->generateTextToImage($inspirationPrompt);

        // ── Afbeeldingen opslaan op schijf ────────────────────────────────────────
        $moodboardPath   = $this->saveImage($moodboardUrl, $submissionId, 'moodboard.png');
        $inspirationPath = $this->saveImage($roomPreviewUrl, $submissionId, 'inspiration.png');

        $resultId = Str::uuid()->toString();

        return array_merge($advice, [
            'resultId'                => $resultId,
            'styleLabel'              => $style,
            'moodboardImageDataUrl'   => $moodboardUrl,
            'roomPreviewImageDataUrl' => $roomPreviewUrl,
            'moodboardPath'           => $moodboardPath,
            'inspirationPath'         => $inspirationPath,
            'emailSent'               => false,
            'emailError'              => null,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────────
    // Tekstadvies via GPT-4o
    // ──────────────────────────────────────────────────────────────────────────────

    private function generateTextAdvice(string $style, string $moodWords, string $colors, string $imageBase64): array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(120)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'    => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    $this->buildUserMessage($style, $moodWords, $colors, $imageBase64),
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature'     => 0.7,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Tekstgeneratie mislukt: ' . $response->json('error.message', 'Onbekende fout'));
        }

        $advice = json_decode($response->json('choices.0.message.content'), true);

        if (! $advice) {
            throw new \RuntimeException('Ongeldig JSON-antwoord van AI ontvangen.');
        }

        return $advice;
    }

    // ──────────────────────────────────────────────────────────────────────────────
    // Ruimteanalyse via GPT-4o vision (voor architectuurbeschrijving)
    // ──────────────────────────────────────────────────────────────────────────────

    private function analyzeRoom(string $imageBase64): string
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'      => $this->model,
                'max_tokens' => 150,
                'messages'   => [
                    [
                        'role'    => 'system',
                        'content' => 'Describe only fixed structural features in one concise English sentence for an image generation prompt: camera angle, window positions, ceiling height, floor surface, wall layout, architectural features. No furniture, colors, or style.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'Describe the fixed architectural structure of this room.'],
                            ['type' => 'image_url', 'image_url' => ['url' => 'data:image/jpeg;base64,' . $imageBase64]],
                        ],
                    ],
                ],
            ]);

        return $response->failed()
            ? 'rectangular living room, eye-level perspective, large window on one wall'
            : trim($response->json('choices.0.message.content') ?? 'rectangular living room with natural light');
    }

    // ──────────────────────────────────────────────────────────────────────────────
    // Text-to-image via gpt-image-1
    // ──────────────────────────────────────────────────────────────────────────────

    private function generateTextToImage(string $prompt): ?string
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(120)
            ->post('https://api.openai.com/v1/images/generations', [
                'model'   => $this->imageModel,
                'prompt'  => $prompt,
                'n'       => 1,
                'size'    => '1024x1024',
                'quality' => 'high',
            ]);

        if ($response->failed()) return null;

        $b64 = $response->json('data.0.b64_json');
        return $b64 ? 'data:image/png;base64,' . $b64 : $response->json('data.0.url');
    }

    // ──────────────────────────────────────────────────────────────────────────────
    // Image-to-image via gpt-image-1 edits, fallback naar text-to-image
    // ──────────────────────────────────────────────────────────────────────────────

    private function generateImageToImage(string $prompt, string $imageBase64): ?string
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(120)
            ->attach('image[]', base64_decode($imageBase64), 'room.jpg', ['Content-Type' => 'image/jpeg'])
            ->post('https://api.openai.com/v1/images/edits', [
                'model'   => $this->imageModel,
                'prompt'  => $prompt,
                'n'       => 1,
                'size'    => '1024x1024',
                'quality' => 'high',
            ]);

        if ($response->failed()) {
            return $this->generateTextToImage($prompt);
        }

        $b64 = $response->json('data.0.b64_json');
        return $b64 ? 'data:image/png;base64,' . $b64 : $response->json('data.0.url');
    }

    // ──────────────────────────────────────────────────────────────────────────────
    // Sla een data-URL op als bestand en geef het relatieve pad terug
    // ──────────────────────────────────────────────────────────────────────────────

    private function saveImage(?string $dataUrl, int $submissionId, string $filename): ?string
    {
        if (! $dataUrl || ! str_starts_with($dataUrl, 'data:')) return null;

        $b64  = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $path = "submissions/{$submissionId}/{$filename}";
        Storage::disk('public')->put($path, base64_decode($b64));

        return $path;
    }

    // ──────────────────────────────────────────────────────────────────────────────
    // Prompts
    // ──────────────────────────────────────────────────────────────────────────────

    private function systemPrompt(): string
    {
        return <<<'SYS'
Je bent een professionele interieurstyliste met diepgaande kennis van interieurstijlen, materialen en kleurgebruik.

Je taak is om interieuradvies te geven dat logisch voortkomt uit de gekozen woonstijl van de gebruiker.

BELANGRIJK: redeneer eerst intern voordat je het advies schrijft.

Volg altijd deze interne stappen:
1. Bepaal eerst welke materialen typisch zijn voor de gekozen interieurstijl.
2. Bepaal daarna welke kleuren logisch passen bij die stijl.
3. Bepaal vervolgens welke meubels en accessoires daarbij aansluiten.
4. Schrijf daarna het advies.

Onderstaande stijl → materiaal logica is alleen ter referentie — kopieer ze niet automatisch:
- Scandinavisch: licht eikenhout, wol, linnen, keramiek, matte verf
- Japandi: eikenhout, bamboe, linnen, papierlampen, keramiek
- Industrieel: staal, beton, leer, ruw hout
- Hotel chic: velours, marmer, messing, donker hout
- Landelijk: grenenhout, vlas, terracotta, riet, keramiek
- Modern: gelakt MDF, glas, RVS, betonlook, microvezel

Gebruik concrete materialen: specifieke houtsoorten, stofsoorten, metaalsoorten, steensoorten en afwerkingen.
Geef NOOIT exacte maten of afmetingen. Gebruik relatieve termen: compact, groot, laag, ruim, diep, slank, oversized.
Zorg dat kleuren, materialen en productideeën samen één samenhangend interieurconcept vormen.
Schrijf warm, persoonlijk en inspirerend.
Retourneer ALLEEN geldig JSON zonder markdown of codeblokken.
SYS;
    }

    private function buildUserMessage(string $style, string $moodWords, string $colors, string $imageBase64): array
    {
        $text  = "Geef gedetailleerd interieuradvies voor een **{$style}** woonkamer.\n";
        if ($moodWords)   $text .= "- Gewenste sfeer: {$moodWords}\n";
        if ($colors)      $text .= "- Voorkeurskleuren: {$colors}\n";
        if ($imageBase64) $text .= "- Foto van huidige woonkamer bijgevoegd — analyseer de ruimte.\n";

        $text .= <<<'EOT'

Retourneer ALLEEN dit JSON-formaat:
{
  "adviceBullets": ["punt 1","punt 2","punt 3","punt 4","punt 5"],
  "palette": [
    {"name":"kleurnaam","hex":"#xxxxxx"},
    {"name":"kleurnaam","hex":"#xxxxxx"},
    {"name":"kleurnaam","hex":"#xxxxxx"},
    {"name":"kleurnaam","hex":"#xxxxxx"},
    {"name":"kleurnaam","hex":"#xxxxxx"}
  ],
  "materials": [
    {"category":"Vloer","recommendations":["optie1","optie2"],"do":["aanrader"],"dont":["vermijden"]},
    {"category":"Meubilair","recommendations":["optie1","optie2"],"do":["aanrader"],"dont":["vermijden"]},
    {"category":"Textiel & Accessoires","recommendations":["optie1","optie2"],"do":["aanrader"],"dont":["vermijden"]}
  ],
  "layoutTips": ["tip1","tip2","tip3","tip4"],
  "productIdeas": [
    {"category":"Bank","exampleSpecs":"beschrijving","material":"materiaal","colorHint":"kleur"},
    {"category":"Salontafel","exampleSpecs":"beschrijving","material":"materiaal","colorHint":"kleur"},
    {"category":"Verlichting","exampleSpecs":"beschrijving","material":"materiaal","colorHint":"kleur"},
    {"category":"Wanddecoratie","exampleSpecs":"beschrijving","material":"materiaal","colorHint":"kleur"}
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
                ['type' => 'image_url', 'image_url' => ['url' => 'data:image/jpeg;base64,' . $imageBase64]],
            ],
        ];
    }

    private function buildMoodboardPrompt(string $style, string $moodWords, array $advice): string
    {
        $paletteStr   = implode(', ', array_map(fn($c) => "{$c['name']} {$c['hex']}", array_slice($advice['palette'] ?? [], 0, 5)));
        $materialsStr = implode(', ', array_filter(array_map(fn($m) => $m['recommendations'][0] ?? null, $advice['materials'] ?? [])));
        $productsStr  = implode(', ', array_map(fn($p) => "{$p['category']} in {$p['material']}", array_slice($advice['productIdeas'] ?? [], 0, 4)));

        return implode(' ', array_filter([
            "Flat lay studio photograph of a professional interior design sample board.",
            "Top-down bird's eye view on a plain white surface.",
            "Contains: color swatch cards in {$paletteStr}; material texture chips: {$materialsStr}; product cutouts: {$productsStr}; fabric swatches, ceramic vessels, botanical sprigs.",
            $moodWords ? "Mood: {$moodWords}." : null,
            "Style: {$style}.",
            "No room, no walls, no floors, no perspective render.",
            "No text, no numbers, no labels, no color codes anywhere.",
        ]));
    }

    private function buildInspirationPrompt(string $style, array $advice, string $roomDescription = ''): string
    {
        $paletteStr   = implode(', ', array_map(fn($c) => $c['name'], array_slice($advice['palette'] ?? [], 0, 5)));
        $materialsStr = implode(', ', array_filter(array_map(fn($m) => $m['recommendations'][0] ?? null, array_slice($advice['materials'] ?? [], 0, 2))));

        $shared = implode(' ', array_filter([
            "Interior style: {$style}.",
            $paletteStr   ? "Color palette: {$paletteStr}." : null,
            $materialsStr ? "Key materials: {$materialsStr}." : null,
            "Photorealistic interior photography, natural daylight. No text, no watermarks.",
        ]));

        if (! $roomDescription) {
            return "Beautifully redesigned living room in {$style} style. {$shared}";
        }

        return "Photorealistic interior redesign based on the provided room photo. Preserve: {$roomDescription}. Keep room layout, window placement, ceiling height, perspective. Only restyle furniture, wall colors, flooring, textiles, lighting, accessories. {$shared} Do not change architecture or perspective.";
    }
}
