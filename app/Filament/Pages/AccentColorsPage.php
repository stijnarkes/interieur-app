<?php

namespace App\Filament\Pages;

use App\Models\AccentColor;
use App\Support\QuizStructure;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Beheert de centrale accentkleurencatalogus voor de accentkleurstap ná de stijlberekening (zie
 * App\Services\AccentColorSelector) — losstaand van StyleProfilesPage's interne accent_colors-
 * repeater, zodat een kleur bij meerdere stijlen mag horen zonder duplicatie. Zelfde
 * lijst-met-kaarten/sleep-herorden-opzet als QuizOptionsPage.
 */
class AccentColorsPage extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Accentkleuren';

    protected static ?string $title = 'Accentkleuren';

    protected static ?string $slug = 'accentkleuren';

    protected static ?string $navigationGroup = 'Quizbeheer';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.accent-colors-page';

    public static function canAccess(): bool
    {
        return Auth::user()?->canManageQuiz() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [$this->createColorAction()];
    }

    /** @return array<int, AccentColor> in sort_order-volgorde (zie AccentColor::booted()) */
    public function getColors(): array
    {
        return AccentColor::query()->get()->all();
    }

    /** @return array<int, mixed> */
    private function formFields(): array
    {
        return [
            TextInput::make('name')
                ->label('Naam')
                ->required()
                ->maxLength(255),

            ColorPicker::make('hex')
                ->label('Kleur')
                ->required(),

            CheckboxList::make('style_keys')
                ->label('Woonstijlen')
                ->helperText('Vink alle woonstijlen aan waarbij deze kleur als accentkleur aangeboden mag worden — verplicht minstens 1. Eén kleur mag bij meerdere stijlen horen.')
                ->options(QuizStructure::styleOptions())
                ->columns(2)
                ->required(),

            Toggle::make('is_active')
                ->label('Actief')
                ->helperText('Inactieve kleuren worden niet meer aangeboden in de klant-quiz.')
                ->default(true),
        ];
    }

    public function createColorAction(): Action
    {
        return Action::make('createColor')
            ->label('Kleur toevoegen')
            ->icon('heroicon-o-plus')
            ->modalHeading('Nieuwe accentkleur toevoegen')
            ->form($this->formFields())
            ->action(function (array $data): void {
                $nextOrder = (AccentColor::withoutGlobalScopes()->max('sort_order') ?? 0) + 10;

                AccentColor::create([...$data, 'sort_order' => $nextOrder]);

                Notification::make()->title('Accentkleur toegevoegd')->success()->send();
            });
    }

    public function editColorAction(): Action
    {
        return Action::make('editColor')
            ->label('Bewerken')
            ->modalHeading('Accentkleur bewerken')
            ->fillForm(fn (array $arguments): array => AccentColor::findOrFail($arguments['colorId'])->only(['name', 'hex', 'style_keys', 'is_active']))
            ->form($this->formFields())
            ->action(function (array $arguments, array $data): void {
                AccentColor::findOrFail($arguments['colorId'])->update($data);

                Notification::make()->title('Accentkleur bijgewerkt')->success()->send();
            });
    }

    public function deleteColorAction(): Action
    {
        return Action::make('deleteColor')
            ->label('Verwijderen')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Accentkleur verwijderen?')
            ->modalDescription('Al opgeslagen resultaten/PDF\'s waarin een bezoeker deze kleur eerder koos, blijven ongewijzigd — die bewaren naam en hexcode zelf. Dit kan niet ongedaan worden gemaakt.')
            ->action(function (array $arguments): void {
                AccentColor::findOrFail($arguments['colorId'])->delete();

                Notification::make()->title('Accentkleur verwijderd')->success()->send();
            });
    }

    public function toggleActive(int $colorId): void
    {
        $record = AccentColor::findOrFail($colorId);
        $record->update(['is_active' => ! $record->is_active]);
    }

    /**
     * @param  array<int, string>  $orderedIds  kleur-id's (als string, zo levert Sortable.js ze aan) in de nieuwe volgorde
     */
    public function reorderColors(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $colorId) {
            AccentColor::where('id', (int) $colorId)->update(['sort_order' => ($index + 1) * 10]);
        }
    }
}
