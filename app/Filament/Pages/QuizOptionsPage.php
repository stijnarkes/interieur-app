<?php

namespace App\Filament\Pages;

use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Support\QuizStructure;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Vervangt de vroegere QuizOptionResource (platte, gegroepeerde tabel) door een pagina met één
 * inklapbare sectie per quizvraag — elk met een eigen "Optie toevoegen"-knop in de kop, en
 * knoppen om de vraag zelf te herordenen/bewerken/verwijderen. De twee secties (Kleur &
 * materiaal / Wonen & inrichting) liggen vast; de vragen zelf staan in `quiz_questions` en
 * zijn hier volledig admin-beheerbaar. Zelfde aanpak als SitePhotosPage.
 */
class QuizOptionsPage extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Antwoordopties';

    protected static ?string $title = 'Antwoordopties';

    protected static ?string $slug = 'quiz-opties';

    protected static ?string $navigationGroup = 'Quizbeheer';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.quiz-options-page';

    /**
     * De enige toegestane matchscores voor styleScoreFields() hieronder — zie de opdracht "scoring
     * woonstijltest Boer Staphorst". String-sleutels (i.p.v. floats: PHP zou 0.30/0.50/0.70/0.85 als
     * array-sleutel stuklopen op float-naar-int-afkapping) — de action-handlers zetten de door
     * Filament teruggegeven string expliciet om naar een float vóór het opslaan.
     */
    private const STYLE_SCORE_OPTIONS = [
        '0.00' => '0,00',
        '0.30' => '0,30',
        '0.50' => '0,50',
        '0.70' => '0,70',
        '0.85' => '0,85',
        '1.00' => '1,00',
    ];

    public static function canAccess(): bool
    {
        return Auth::user()?->canManageQuiz() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [$this->createQuestionAction()];
    }

    /** @return array<int, QuizQuestion> in vaste sectievolgorde, dan op sort_order binnen de sectie */
    public function getQuestions(): array
    {
        $sectionOrder = array_flip(array_keys(QuizStructure::SECTIONS));

        return QuizQuestion::query()
            ->with(['options' => fn ($query) => $query->orderBy('sort_order')->with('styleLinks')])
            ->get()
            ->sortBy(fn (QuizQuestion $question): string => sprintf('%d-%08d', $sectionOrder[$question->section] ?? 99, $question->sort_order))
            ->values()
            ->all();
    }

    /** @return array<int, mixed> */
    private function colorMetadataFields(): array
    {
        return [
            ColorPicker::make('color_hex')->label('Kleur'),
            TextInput::make('color_family')->label('Kleurfamilie'),
            TextInput::make('color_temperature')->label('Temperatuur'),
        ];
    }

    /** @return array<int, mixed> */
    private function internalNoteField(): array
    {
        return [
            Textarea::make('internal_note')
                ->label('Interne notitie voor de styliste')
                ->helperText('Alleen zichtbaar in het interne overzicht, nooit voor de bezoeker.')
                ->rows(2),
        ];
    }

    /**
     * Welke stijlen aan deze optie "gekoppeld" zijn (QuizOption::style_keys/style_key) wordt niet
     * meer los aangevinkt — dat volgt nu automatisch uit de stijlscores hieronder (styleScoreFields()):
     * elke stijl met een score hoger dan 0,00 telt als gekoppeld. Dit blijft nodig voor andere
     * plekken die linkedStyleKeys() gebruiken (QuizConfigController/QuizPreviewController sluiten
     * een optie zonder gekoppelde stijl helemaal uit van de klant-quiz, QuizLeadController sorteert
     * het moodboard erop) — vandaar dat de create/edit-actions dit altijd zelf afleiden i.p.v. de
     * admin twee keer (los aanvinken én scoren) hetzelfde te laten invullen.
     *
     * @param  array<string, float>  $styleScores
     * @return array<int, string>
     */
    private function deriveStyleKeysFromScores(array $styleScores): array
    {
        return collect($styleScores)
            ->filter(fn (float $score): bool => $score > 0.0)
            ->keys()
            ->values()
            ->all();
    }

    /**
     * Eén dropdown per stijl met de matchscore (0-1) van deze optie voor de uitslagberekening van
     * de woonstijltest (zie QuizScoringService/QuizOption::style_scores) — bewust beperkt tot de
     * zes vaste waarden uit de opdracht "scoring woonstijltest Boer Staphorst" i.p.v. een vrij
     * getal, zodat de scores onderling vergelijkbaar blijven. De zes scores van één optie hoeven
     * niet tot 1 op te tellen; ze zijn onafhankelijk van elkaar.
     *
     * @return array<int, mixed>
     */
    private function styleScoreFields(): array
    {
        return collect(QuizStructure::styleKeys())
            ->map(fn (string $styleKey) => Select::make("style_scores.{$styleKey}")
                ->label(QuizStructure::styleLabel($styleKey))
                ->options(self::STYLE_SCORE_OPTIONS)
                ->default('0.00')
                ->required())
            ->values()
            ->all();
    }

    /** @return array<int, mixed> */
    private function productFields(): array
    {
        return [
            TextInput::make('product_name')->label('Productnaam'),
            TextInput::make('sku')->label('SKU'),
            TextInput::make('brand')->label('Merk'),
            TextInput::make('product_url')->label('Product-URL')->url(),
            TextInput::make('price')->label('Prijs')->numeric()->prefix('€'),
            Toggle::make('showroom_product')->label('Showroomproduct'),
        ];
    }

    public function createOptionAction(): Action
    {
        return Action::make('createOption')
            ->label('Optie toevoegen')
            ->icon('heroicon-o-plus')
            ->modalHeading('Nieuwe antwoordoptie toevoegen')
            ->form([
                TextInput::make('title')
                    ->label('Titel')
                    ->required()
                    ->maxLength(255),

                FormSection::make('Stijlscores')
                    ->description('Matchscore per stijl (0,00-1,00) — bepaalt zowel de uitslagberekening van de woonstijltest als bij welke stijlen deze optie hoort. Vul minstens één stijl hoger dan 0,00 in, anders verschijnt de optie nergens in de klant-quiz.')
                    ->schema($this->styleScoreFields()),

                ...$this->internalNoteField(),

                FileUpload::make('image')
                    ->label('Afbeelding')
                    ->image()
                    ->required()
                    ->maxSize(10240)
                    ->disk('public')
                    ->directory('tmp-quiz-uploads')
                    ->visibility('private'),

                Toggle::make('is_active')
                    ->label('Actief')
                    ->default(true),

                FormSection::make('Kleurmetadata')
                    ->collapsed()
                    ->schema($this->colorMetadataFields()),

                FormSection::make('Toon-/verkoopinformatie')
                    ->collapsed()
                    ->schema($this->productFields()),
            ])
            ->action(function (array $arguments, array $data, Action $action): void {
                $questionId = $arguments['questionId'];
                $slug = Str::slug("{$questionId}-{$data['title']}").'-'.Str::random(5);

                $uploadedImage = $data['image'];
                unset($data['image']);

                // Filament geeft de Select-waarden als strings terug (zie STYLE_SCORE_OPTIONS) —
                // hier expliciet naar float, anders staat er "0.30" (string) i.p.v. 0.3 in de JSON.
                $data['style_scores'] = array_map('floatval', $data['style_scores'] ?? []);
                $data['style_keys'] = $this->deriveStyleKeysFromScores($data['style_scores']);

                if ($data['style_keys'] === []) {
                    Notification::make()
                        ->title('Vul minstens één stijlscore hoger dan 0,00 in')
                        ->body('Zonder dat blijft deze optie voor de bezoeker onvindbaar in de klant-quiz.')
                        ->danger()
                        ->send();

                    $action->halt();
                }

                $nextOrder = (QuizOption::where('question_id', $questionId)->max('sort_order') ?? 0) + 10;

                $option = QuizOption::create([
                    ...$data,
                    'question_id' => $questionId,
                    'sort_order' => $nextOrder,
                    'style_key' => $data['style_keys'][0],
                    'option_slug' => $slug,
                    'image_path' => "/images/interior/extra/{$slug}.webp",
                ]);

                $option->storeImage($uploadedImage);

                Notification::make()->title('Antwoordoptie toegevoegd')->success()->send();
            });
    }

    public function editOptionAction(): Action
    {
        return Action::make('editOption')
            ->label('Bewerken')
            ->modalHeading('Antwoordoptie bewerken')
            ->fillForm(function (array $arguments): array {
                $option = QuizOption::findOrFail($arguments['optionId']);

                return [
                    ...$option->toArray(),
                    // Terug naar de string-vorm die de Select-opties gebruiken (zie
                    // STYLE_SCORE_OPTIONS) — anders herkent Filament een opgeslagen 0.3 (float)
                    // niet als de geselecteerde "0.30"-optie.
                    'style_scores' => collect(QuizStructure::styleKeys())
                        ->mapWithKeys(fn (string $styleKey): array => [$styleKey => number_format($option->scoreFor($styleKey), 2)])
                        ->all(),
                ];
            })
            ->form([
                TextInput::make('title')
                    ->label('Titel')
                    ->required()
                    ->maxLength(255),

                FormSection::make('Stijlscores')
                    ->description('Matchscore per stijl (0,00-1,00) — bepaalt zowel de uitslagberekening van de woonstijltest als bij welke stijlen deze optie hoort. Vul minstens één stijl hoger dan 0,00 in, anders verschijnt de optie nergens in de klant-quiz.')
                    ->schema($this->styleScoreFields()),

                ...$this->internalNoteField(),

                Toggle::make('is_active')
                    ->label('Actief')
                    ->helperText('Inactieve opties worden niet meer getoond in de klant-quiz.'),

                FileUpload::make('image')
                    ->label('Nieuwe afbeelding')
                    ->helperText('Laat leeg om de huidige afbeelding te behouden.')
                    ->image()
                    ->maxSize(10240)
                    ->disk('public')
                    ->directory('tmp-quiz-uploads')
                    ->visibility('private')
                    ->dehydrated(fn ($state): bool => filled($state)),

                FormSection::make('Kleurmetadata')
                    ->collapsed()
                    ->schema($this->colorMetadataFields()),

                FormSection::make('Toon-/verkoopinformatie')
                    ->collapsed()
                    ->schema($this->productFields()),
            ])
            ->action(function (array $arguments, array $data, Action $action): void {
                $record = QuizOption::findOrFail($arguments['optionId']);

                // Zie createOptionAction() voor waarom dit nodig is: Filament geeft de
                // Select-waarden als strings terug.
                $data['style_scores'] = array_map('floatval', $data['style_scores'] ?? []);
                $data['style_keys'] = $this->deriveStyleKeysFromScores($data['style_scores']);

                if ($data['style_keys'] === []) {
                    Notification::make()
                        ->title('Vul minstens één stijlscore hoger dan 0,00 in')
                        ->body('Zonder dat blijft deze optie voor de bezoeker onvindbaar in de klant-quiz.')
                        ->danger()
                        ->send();

                    $action->halt();
                }

                $data['style_key'] = $data['style_keys'][0];

                if (! empty($data['image'])) {
                    $record->storeImage($data['image']);
                }
                unset($data['image']);

                $record->update($data);

                Notification::make()->title('Antwoordoptie bijgewerkt')->success()->send();
            });
    }

    public function deleteImageAction(): Action
    {
        return Action::make('deleteImage')
            ->label('Verwijder afbeelding')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Afbeelding verwijderen?')
            ->modalDescription('Hierna toont de quiz op deze plek weer de placeholder, totdat je een nieuwe foto uploadt.')
            ->action(function (array $arguments): void {
                QuizOption::findOrFail($arguments['optionId'])->deleteImage();

                Notification::make()->title('Afbeelding verwijderd')->success()->send();
            });
    }

    public function deleteOptionAction(): Action
    {
        return Action::make('deleteOption')
            ->label('Verwijderen')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Antwoordoptie verwijderen?')
            ->modalDescription('Deze optie verdwijnt volledig uit de vraag. Dit kan niet ongedaan worden gemaakt.')
            ->action(function (array $arguments): void {
                $record = QuizOption::findOrFail($arguments['optionId']);
                $record->deleteImage();
                $record->delete();

                Notification::make()->title('Antwoordoptie verwijderd')->success()->send();
            });
    }

    public function toggleActive(int $optionId): void
    {
        $record = QuizOption::findOrFail($optionId);
        $record->update(['is_active' => ! $record->is_active]);
    }

    public function createQuestionAction(): Action
    {
        return Action::make('createQuestion')
            ->label('Nieuwe vraag toevoegen')
            ->icon('heroicon-o-plus')
            ->color('gray')
            ->modalHeading('Nieuwe vraag toevoegen')
            ->form([
                TextInput::make('title')
                    ->label('Vraagtekst')
                    ->required()
                    ->maxLength(255),

                Select::make('section')
                    ->label('Sectie')
                    ->options(QuizStructure::sectionOptions())
                    ->required(),

                TextInput::make('max_selections')
                    ->label('Maximum aantal keuzes')
                    ->helperText('Hoeveel opties mag een bezoeker bij deze vraag tegelijk kiezen? Standaard 1 (één keuze).')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(10)
                    ->default(1)
                    ->required(),

                TextInput::make('weight')
                    ->label('Gewicht')
                    ->helperText('Hoe zwaar telt deze vraag mee in de uitslag? Standaard 1. Een zwaardere vraag (bv. 1,5) telt harder mee.')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(10)
                    ->step(0.1)
                    ->default(1)
                    ->required(),

                Select::make('image_display_mode')
                    ->label('Weergave foto\'s')
                    ->options(QuizStructure::imageDisplayModeOptions())
                    ->default('contain')
                    ->required(),

                Select::make('room')
                    ->label('Ruimte')
                    ->helperText('Voor welke kamer geeft deze vraag een voorkeur? Laat leeg als de vraag niet aan één ruimte gebonden is (bv. een algemene materiaal-/kleurvraag).')
                    ->options(QuizStructure::roomOptions())
                    ->nullable(),
            ])
            ->action(function (array $data): void {
                $nextOrder = (QuizQuestion::where('section', $data['section'])->max('sort_order') ?? 0) + 10;

                QuizQuestion::create([
                    'question_key' => Str::slug($data['title']).'-'.Str::random(5),
                    'section' => $data['section'],
                    'room' => $data['room'] ?? null,
                    'title' => $data['title'],
                    'folder' => null,
                    'sort_order' => $nextOrder,
                    'max_selections' => $data['max_selections'],
                    'image_display_mode' => $data['image_display_mode'],
                ]);

                Notification::make()
                    ->title('Vraag toegevoegd')
                    ->body('Voeg er nu antwoordopties met eigen foto\'s bij toe — een nieuwe vraag heeft nog geen kant-en-klare fotoset.')
                    ->success()
                    ->send();
            });
    }

    public function editQuestionAction(): Action
    {
        return Action::make('editQuestion')
            ->label('Vraag bewerken')
            ->modalHeading('Vraag bewerken')
            ->fillForm(fn (array $arguments): array => QuizQuestion::findOrFail($arguments['questionId'])->only(['title', 'section', 'max_selections', 'weight', 'image_display_mode', 'room']))
            ->form([
                TextInput::make('title')
                    ->label('Vraagtekst')
                    ->required()
                    ->maxLength(255),

                Select::make('section')
                    ->label('Sectie')
                    ->options(QuizStructure::sectionOptions())
                    ->required()
                    ->helperText('Verplaats je de vraag naar de andere sectie, dan komt hij daar achteraan te staan.'),

                TextInput::make('max_selections')
                    ->label('Maximum aantal keuzes')
                    ->helperText('Hoeveel opties mag een bezoeker bij deze vraag tegelijk kiezen? Standaard 1 (één keuze).')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(10)
                    ->required(),

                TextInput::make('weight')
                    ->label('Gewicht')
                    ->helperText('Hoe zwaar telt deze vraag mee in de uitslag? Standaard 1. Een zwaardere vraag (bv. 1,5) telt harder mee.')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(10)
                    ->step(0.1)
                    ->required(),

                Select::make('image_display_mode')
                    ->label('Weergave foto\'s')
                    ->options(QuizStructure::imageDisplayModeOptions())
                    ->required(),

                Select::make('room')
                    ->label('Ruimte')
                    ->helperText('Voor welke kamer geeft deze vraag een voorkeur? Laat leeg als de vraag niet aan één ruimte gebonden is (bv. een algemene materiaal-/kleurvraag).')
                    ->options(QuizStructure::roomOptions())
                    ->nullable(),
            ])
            ->action(function (array $arguments, array $data): void {
                $question = QuizQuestion::findOrFail($arguments['questionId']);

                if ($data['section'] !== $question->section) {
                    $data['sort_order'] = (QuizQuestion::where('section', $data['section'])->max('sort_order') ?? 0) + 10;
                }

                $question->update($data);

                Notification::make()->title('Vraag bijgewerkt')->success()->send();
            });
    }

    public function deleteQuestionAction(): Action
    {
        return Action::make('deleteQuestion')
            ->label('Vraag verwijderen')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Vraag verwijderen?')
            ->modalDescription('Hiermee verdwijnen ook alle antwoordopties (en hun foto\'s) die aan deze vraag hangen. Dit kan niet ongedaan worden gemaakt.')
            ->action(function (array $arguments): void {
                $question = QuizQuestion::findOrFail($arguments['questionId']);

                $question->options->each(function (QuizOption $option): void {
                    $option->deleteImage();
                    $option->delete();
                });

                $question->delete();

                Notification::make()->title('Vraag verwijderd')->success()->send();
            });
    }

    /**
     * Slaat de nieuwe volgorde op na slepen — zie x-sortable in de Blade-view. Elke sectie heeft
     * haar eigen sleepbare lijst, dus `$orderedIds` bevat altijd alleen vraag-id's uit één
     * sectie; vragen kunnen daardoor nooit per ongeluk van sectie wisselen door te slepen.
     *
     * @param  array<int, string>  $orderedIds  vraag-id's (als string, zo levert Sortable.js ze aan) in de nieuwe volgorde
     */
    public function reorderQuestions(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $questionId) {
            QuizQuestion::where('id', (int) $questionId)->update(['sort_order' => ($index + 1) * 10]);
        }
    }

    /**
     * Slaat de nieuwe volgorde van antwoordopties op na slepen — zie x-sortable in de Blade-view.
     * Elke vraag heeft haar eigen sleepbare optielijst, dus `$orderedIds` bevat altijd alleen
     * optie-id's uit één vraag; opties kunnen daardoor nooit per ongeluk bij een andere vraag
     * terechtkomen door te slepen.
     *
     * @param  array<int, string>  $orderedIds  optie-id's (als string, zo levert Sortable.js ze aan) in de nieuwe volgorde
     */
    public function reorderOptions(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $optionId) {
            QuizOption::where('id', (int) $optionId)->update(['sort_order' => ($index + 1) * 10]);
        }
    }
}
