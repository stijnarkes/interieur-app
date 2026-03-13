<?php

namespace Database\Seeders;

use App\Models\PromptSetting;
use Illuminate\Database\Seeder;

class PromptSettingSeeder extends Seeder
{
    public function run(): void
    {
        $prompts = [
            [
                'key'   => 'system_prompt',
                'label' => 'Systeem prompt (tekstadvies)',
                'value' => <<<'PROMPT'
Je bent een professionele interieurstyliste met diepgaande kennis van interieurstijlen, materialen en kleurgebruik.

Je taak is om interieuradvies te geven dat logisch voortkomt uit de gekozen woonstijl van de gebruiker.

BELANGRIJK: redeneer eerst intern voordat je het advies schrijft.

Volg altijd deze interne stappen:
1. Bepaal eerst welke materialen typisch zijn voor de gekozen interieurstijl.
2. Bepaal daarna welke kleuren logisch passen bij die stijl.
3. Bepaal vervolgens welke meubels en accessoires daarbij aansluiten.
4. Schrijf daarna het advies.

Het advies moet dus altijd ontstaan vanuit de stijl, niet vanuit voorbeelden in de prompt.

Gebruik concrete materialen: specifieke houtsoorten, stofsoorten, metaalsoorten, steensoorten en afwerkingen.
Geef NOOIT exacte maten of afmetingen. Gebruik relatieve termen: compact, groot, laag, ruim, diep, slank, oversized.
Zorg dat kleuren, materialen en productideeën samen één samenhangend interieurconcept vormen.
Noem nooit vage termen als "mooie neutrale tinten" zonder ze te concretiseren.
Schrijf warm, persoonlijk en inspirerend — als een styliste die persoonlijk advies geeft, niet als een productspecificatie.
Retourneer ALLEEN geldig JSON zonder markdown of codeblokken.
PROMPT,
            ],
            [
                'key'   => 'moodboard_prompt_suffix',
                'label' => 'Moodboard prompt aanvulling',
                'value' => 'This must look exactly like a physical designer sample board photographed from directly above. Restrictions: do not draw a room, do not draw walls or floors or ceilings, do not draw a sofa scene, do not draw any interior perspective. Text restrictions: no text, no numbers, no letters, no labels, no captions, no color codes, no hex codes, no annotations anywhere.',
            ],
            [
                'key'   => 'inspiration_prompt_suffix',
                'label' => 'Inspiratiebeeld prompt aanvulling',
                'value' => 'Photorealistic interior photography, natural daylight, architectural quality. No text, no watermarks, no labels anywhere in the image.',
            ],
            [
                'key'   => 'user_message_intro',
                'label' => 'Gebruikersbericht intro tekst',
                'value' => 'Geef gedetailleerd interieuradvies voor een **{style}** woonkamer. Baseer het advies op de volgende klantinformatie:',
            ],
        ];

        foreach ($prompts as $prompt) {
            PromptSetting::updateOrCreate(
                ['key' => $prompt['key']],
                [
                    'label' => $prompt['label'],
                    'value' => $prompt['value'],
                ]
            );
        }
    }
}
