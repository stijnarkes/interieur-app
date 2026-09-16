<?php

namespace App\Filament\Pages;

use App\Models\BasePalette;
use App\Support\QuizStructure;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Beheert de basispaletten-catalogus waaruit de bezoeker er ná de stijlberekening één kiest (zie
 * App\Models\BasePalette) — één inklapbare sectie per vaste woonstijl (net als QuizOptionsPage
 * per quizvraag), elk met een eigen "Palet toevoegen"-knop en per palet-kaart sleep/bewerk/
 * verwijder. Anders dan AccentColorsPage (een gedeelde catalogus over stijlen heen) hoort een
 * basispalet altijd bij precies één stijl, dus is er geen CheckboxList met woonstijlen — de
 * stijl ligt al vast aan de groep waarin het palet staat.
 */
class BasePalettesPage extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationLabel = 'Basispaletten';

    protected static ?string $title = 'Basispaletten';

    protected static ?string $slug = 'basispaletten';

    protected static ?string $navigationGroup = 'Quizbeheer';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.base-palettes-page';

    public static function canAccess(): bool
    {
        return Auth::user()?->canManageQuiz() ?? false;
    }

    /** @return array<string, \Illuminate\Support\Collection<int, BasePalette>> per stijl, in vaste QuizStructure-volgorde */
    public function getPalettesByStyle(): array
    {
        $grouped = BasePalette::query()->get()->groupBy('style_key');

        return collect(QuizStructure::styleOptions())
            ->mapWithKeys(fn (string $label, string $styleKey) => [$styleKey => $grouped->get($styleKey, collect())])
            ->all();
    }

    /** @return array<int, mixed> */
    private function formFields(): array
    {
        return [
            TextInput::make('name')
                ->label('Sfeernaam')
                ->helperText('Bv. "Warm en geborgen" — de titel die de bezoeker op de kaart ziet.')
                ->required()
                ->maxLength(255),

            Textarea::make('description')
                ->label('Sfeeromschrijving')
                ->helperText('Een korte zin die de sfeer van dit palet uitlegt.')
                ->rows(2)
                ->required(),

            Repeater::make('colors')
                ->label('Kleuren')
                ->helperText('De hex-codes worden nooit rechtstreeks aan de bezoeker getoond, alleen als kleurvlak met naam.')
                ->schema([
                    TextInput::make('name')->label('Naam')->required(),
                    ColorPicker::make('hex')->label('Kleur')->required(),
                ])
                ->columns(2)
                ->minItems(1)
                ->addActionLabel('Kleur toevoegen'),

            Toggle::make('is_active')
                ->label('Actief')
                ->helperText('Inactieve paletten worden niet meer aangeboden in de klant-quiz.')
                ->default(true),
        ];
    }

    public function createPaletteAction(): Action
    {
        return Action::make('createPalette')
            ->label('Palet toevoegen')
            ->icon('heroicon-o-plus')
            ->modalHeading('Nieuw basispalet toevoegen')
            ->form($this->formFields())
            ->action(function (array $arguments, array $data): void {
                $styleKey = $arguments['styleKey'];
                $nextOrder = (BasePalette::withoutGlobalScopes()->where('style_key', $styleKey)->max('sort_order') ?? 0) + 10;

                BasePalette::create([...$data, 'style_key' => $styleKey, 'sort_order' => $nextOrder]);

                Notification::make()->title('Basispalet toegevoegd')->success()->send();
            });
    }

    public function editPaletteAction(): Action
    {
        return Action::make('editPalette')
            ->label('Bewerken')
            ->modalHeading('Basispalet bewerken')
            ->fillForm(fn (array $arguments): array => BasePalette::findOrFail($arguments['paletteId'])->only(['name', 'description', 'colors', 'is_active']))
            ->form($this->formFields())
            ->action(function (array $arguments, array $data): void {
                BasePalette::findOrFail($arguments['paletteId'])->update($data);

                Notification::make()->title('Basispalet bijgewerkt')->success()->send();
            });
    }

    public function deletePaletteAction(): Action
    {
        return Action::make('deletePalette')
            ->label('Verwijderen')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Basispalet verwijderen?')
            ->modalDescription('Al opgeslagen resultaten/PDF\'s waarin een bezoeker dit palet eerder koos, blijven ongewijzigd — die bewaren naam/omschrijving/kleuren zelf. Dit kan niet ongedaan worden gemaakt.')
            ->action(function (array $arguments): void {
                BasePalette::findOrFail($arguments['paletteId'])->delete();

                Notification::make()->title('Basispalet verwijderd')->success()->send();
            });
    }

    public function toggleActive(int $paletteId): void
    {
        $record = BasePalette::findOrFail($paletteId);
        $record->update(['is_active' => ! $record->is_active]);
    }

    /**
     * Slaat de nieuwe volgorde op na slepen — zie x-sortable in de Blade-view. Elke stijl heeft
     * haar eigen sleepbare lijst, dus `$orderedIds` bevat altijd alleen paletten uit één stijl.
     *
     * @param  array<int, string>  $orderedIds  palet-id's (als string, zo levert Sortable.js ze aan) in de nieuwe volgorde
     */
    public function reorderPalettes(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $paletteId) {
            BasePalette::where('id', (int) $paletteId)->update(['sort_order' => ($index + 1) * 10]);
        }
    }
}
