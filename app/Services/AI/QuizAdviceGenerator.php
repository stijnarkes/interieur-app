<?php

namespace App\Services\AI;

use App\Models\GeneratedReport;
use App\Models\QuizResult;
use App\Models\StyleProfile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Vertaalt een al volledig berekend QuizResult (zie QuizScoringService) naar leesbare, persoonlijke
 * tekst via OpenAI — en verder niets: AI bepaalt hier nooit scores/stijlen, alleen de formulering.
 * Cachet per (quiz_result, variant) in generated_reports zodat hetzelfde resultaat nooit twee keer
 * gegenereerd wordt, en valt bij elke fout (netwerk, timeout, ongeldige output) terug op
 * QuizAdviceFallback — een bezoeker krijgt hierdoor nooit een kapotte of lege resultaatpagina/PDF.
 */
class QuizAdviceGenerator
{
    private const PROMPT_VERSION = 'v1';

    private const PROMPT_PATH = 'prompts/quiz-advice.v1.md';

    public function __construct(
        private readonly QuizAdviceValidator $validator,
        private readonly QuizAdviceFallback $fallback,
    ) {}

    /**
     * @return array{comboName: string, intro: string, roomAdvice?: array<string, ?string>}
     */
    public function generate(QuizResult $result, string $variant): array
    {
        $existing = GeneratedReport::query()
            ->where('quiz_result_id', $result->id)
            ->where('variant', $variant)
            ->first();

        if ($existing && $existing->status === 'succeeded') {
            return $existing->output;
        }

        $summary = $this->buildSummary($result);
        $styleContentVersion = $this->styleContentVersion($result);

        $report = $existing ?? new GeneratedReport([
            'quiz_result_id' => $result->id,
            'variant' => $variant,
        ]);
        $report->prompt_version = self::PROMPT_VERSION;
        $report->style_content_version = $styleContentVersion;
        $report->input_summary = $summary;

        try {
            $output = $this->callOpenAi($summary, $variant);

            if (! $this->validator->validate($output, $variant)) {
                throw new \RuntimeException('AI-output voldeed niet aan de validatie (ontbrekende velden, verkeerde lengte, of gelekte cijfers).');
            }

            $report->fill(['output' => $output, 'status' => 'succeeded', 'error' => null, 'generated_at' => now()]);
            $report->save();

            return $output;
        } catch (Throwable $e) {
            Log::channel('quiz_ai')->warning('Quiz-adviesgeneratie mislukt, terugvallen op sjabloontekst.', [
                'quiz_result_id' => $result->id,
                'variant' => $variant,
                'error' => $e->getMessage(),
            ]);

            $output = $this->fallback->build($result);
            if ($variant === 'pdf_full') {
                $output['roomAdvice'] = $this->fallback->roomAdvice($result);
            }

            $report->fill(['output' => $output, 'status' => 'fallback', 'error' => $e->getMessage(), 'generated_at' => now()]);
            $report->save();

            return $output;
        }
    }

    /**
     * De compacte, gecontroleerde samenvatting die de AI als enige bron krijgt — zie het
     * implementatieplan. Nooit ruwe percentages of puntentotalen.
     *
     * @return array<string, mixed>
     */
    private function buildSummary(QuizResult $result): array
    {
        $labelFor = fn (?string $key): ?string => $key ? StyleProfile::forStyle($key)?->label ?? $key : null;

        return [
            'primary_style' => $labelFor($result->primary_style),
            'secondary_style' => $labelFor($result->secondary_style),
            'tertiary_style' => $labelFor($result->tertiary_style),
            'strength' => [
                'primary' => $result->primary_strength,
                'secondary' => $result->secondary_strength,
                'tertiary' => $result->tertiary_strength,
            ],
            'case' => $result->case,
            'dominant_traits' => collect($result->dominant_traits ?? [])->pluck('label')->all(),
            'room_profiles' => $this->roomProfileLabels($result),
            'color_preference' => $result->color_preference,
        ];
    }

    /** @return array<string, string> ruimte => dominante stijl(en), bv. "Japandi" of "Japandi / Modern" */
    private function roomProfileLabels(QuizResult $result): array
    {
        $labels = [];

        foreach ($result->room_profiles ?? [] as $room => $percentages) {
            if (array_sum($percentages) <= 0) {
                continue;
            }

            arsort($percentages);
            $topKeys = array_slice(array_keys($percentages), 0, 2);
            $labels[$room] = collect($topKeys)
                ->map(fn (string $key): string => StyleProfile::forStyle($key)?->label ?? $key)
                ->implode(' / ');
        }

        return $labels;
    }

    /** Verandert zodra een admin de betrokken stijlprofielen bewerkt — voor reproduceerbaarheid van oudere rapporten. */
    private function styleContentVersion(QuizResult $result): string
    {
        $keys = array_values(array_filter([$result->primary_style, $result->secondary_style, $result->tertiary_style]));

        if ($keys === []) {
            return '0';
        }

        $latest = StyleProfile::query()->whereIn('style_key', $keys)->max('updated_at');

        return (string) $latest;
    }

    /** @return array<string, mixed> */
    private function callOpenAi(array $summary, string $variant): array
    {
        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            throw new \RuntimeException('Geen OPENAI_API_KEY geconfigureerd.');
        }

        $systemPrompt = file_get_contents(resource_path(self::PROMPT_PATH));

        $schema = $variant === 'pdf_full'
            ? '{"comboName": string, "intro": string, "roomAdvice": {"woonkamer": string|null, "eethoek": string|null, "keuken": string|null}}'
            : '{"comboName": string, "intro": string}';

        $userMessage = "Verwacht JSON-schema (laat een veld null als er geen data voor die ruimte is):\n{$schema}\n\n"
            .'Gegevens over de testuitslag:'."\n".json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $response = Http::withToken($apiKey)
            ->timeout(8)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4o'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException("OpenAI-aanvraag mislukt: HTTP {$response->status()}.");
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || $content === '') {
            throw new \RuntimeException('Geen inhoud in OpenAI-respons.');
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('OpenAI-respons was geen geldige JSON.');
        }

        return $decoded;
    }
}
