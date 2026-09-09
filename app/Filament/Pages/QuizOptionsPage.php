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
 * materiaal / Meubels & accessoires) liggen vast; de vragen zelf staan in `quiz_questions` en
 * zijn hier volledig admin-beheerbaar. Zelfde aanpak als ImageManagerPage.
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
            ->with(['options' => fn ($query) => $query->orderBy('id')->with('styleLinks')])
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

                Select::make('styles')
                    ->label('Gekoppelde woonstijlen')
                    ->helperText('Bepaalt aan welke woonstijl(en) deze keuze punten geeft — kies er meerdere als de foto bij meerdere stijlen past.')
                    ->options(QuizStructure::styleOptions())
                    ->multiple()
                    ->required(),

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
            ->action(function (array $arguments, array $data): void {
                $questionId = $arguments['questionId'];
                $slug = Str::slug("{$questionId}-{$data['title']}").'-'.Str::random(5);

                $uploadedImage = $data['image'];
                unset($data['image']);

                $styles = $data['styles'];
                unset($data['styles']);

                $option = QuizOption::create([
                    ...$data,
                    'question_id' => $questionId,
                    'primary_style' => $styles[0],
                    'style_key' => $styles[0],
                    'option_slug' => $slug,
                    'image_path' => "/images/interior/extra/{$slug}.webp",
                ]);

                $option->syncStyles($styles);
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

                return [...$option->toArray(), 'styles' => $option->styleKeys()];
            })
            ->form([
                TextInput::make('title')
                    ->label('Titel')
                    ->required()
                    ->maxLength(255),

                Select::make('styles')
                    ->label('Gekoppelde woonstijlen')
                    ->helperText('Bepaalt aan welke woonstijl(en) deze keuze punten geeft — kies er meerdere als de foto bij meerdere stijlen past. De afbeelding blijft gekoppeld aan de oorspronkelijke stijl-slot.')
                    ->options(QuizStructure::styleOptions())
                    ->multiple()
                    ->required(),

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
            ->action(function (array $arguments, array $data): void {
                $record = QuizOption::findOrFail($arguments['optionId']);

                if (! empty($data['image'])) {
                    $record->storeImage($data['image']);
                }
                unset($data['image']);

                $styles = $data['styles'];
                unset($data['styles']);

                $record->update([...$data, 'primary_style' => $styles[0]]);
                $record->syncStyles($styles);

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
            ])
            ->action(function (array $data): void {
                $nextOrder = (QuizQuestion::where('section', $data['section'])->max('sort_order') ?? 0) + 10;

                QuizQuestion::create([
                    'question_key' => Str::slug($data['title']).'-'.Str::random(5),
                    'section' => $data['section'],
                    'title' => $data['title'],
                    'folder' => null,
                    'sort_order' => $nextOrder,
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
            ->fillForm(fn (array $arguments): array => QuizQuestion::findOrFail($arguments['questionId'])->only(['title', 'section']))
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
}
