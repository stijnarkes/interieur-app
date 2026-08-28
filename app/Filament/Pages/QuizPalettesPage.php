<?php

namespace App\Filament\Pages;

use App\Models\QuizPalette;
use App\Models\QuizPaletteColor;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Vervangt de vroegere QuizColorResource (losse, individuele kleuren) — sinds de kleurvoorkeur-
 * vraag sfeerpaletten gebruikt in plaats van losse kleuren, is dat scherm niet meer zinvol.
 * Zelfde opzet als QuizOptionsPage/ImageManagerPage: één inklapbare, sleepbare sectie per palet,
 * met daarbinnen de kleuren van dat palet (ook sleepbaar, want de volgorde weegt mee in
 * paletteEngine.js — de eerste kleur telt het zwaarst).
 */
class QuizPalettesPage extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationLabel = 'Kleurvoorkeur';

    protected static ?string $title = 'Sfeerpaletten beheren';

    protected static ?string $slug = 'kleurvoorkeur';

    protected static ?string $navigationGroup = 'Quizbeheer';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.quiz-palettes-page';

    public static function canAccess(): bool
    {
        return Auth::user()?->canManageQuiz() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [$this->createPaletteAction()];
    }

    /** @return \Illuminate\Support\Collection<int, QuizPalette> in sort_order, met kleuren erin geladen */
    public function getPalettes()
    {
        return QuizPalette::query()
            ->with(['colors' => fn ($query) => $query->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();
    }

    public function createPaletteAction(): Action
    {
        return Action::make('createPalette')
            ->label('Nieuw palet toevoegen')
            ->icon('heroicon-o-plus')
            ->color('gray')
            ->modalHeading('Nieuw sfeerpalet toevoegen')
            ->form([
                TextInput::make('name')
                    ->label('Naam')
                    ->helperText('Een sfeernaam, geen woonstijlnaam — bv. "Zacht & aards".')
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (array $data): void {
                $nextOrder = (QuizPalette::max('sort_order') ?? 0) + 10;

                QuizPalette::create([
                    'palette_key' => Str::slug($data['name']).'-'.Str::random(5),
                    'name' => $data['name'],
                    'sort_order' => $nextOrder,
                ]);

                Notification::make()
                    ->title('Palet toegevoegd')
                    ->body('Voeg er nu kleuren aan toe — een leeg palet toont niets in de quiz.')
                    ->success()
                    ->send();
            });
    }

    public function editPaletteAction(): Action
    {
        return Action::make('editPalette')
            ->label('Palet bewerken')
            ->modalHeading('Palet bewerken')
            ->fillForm(fn (array $arguments): array => QuizPalette::findOrFail($arguments['paletteId'])->only(['name']))
            ->form([
                TextInput::make('name')
                    ->label('Naam')
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (array $arguments, array $data): void {
                QuizPalette::findOrFail($arguments['paletteId'])->update($data);

                Notification::make()->title('Palet bijgewerkt')->success()->send();
            });
    }

    public function deletePaletteAction(): Action
    {
        return Action::make('deletePalette')
            ->label('Palet verwijderen')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Palet verwijderen?')
            ->modalDescription('Het palet en al zijn kleuren verdwijnen volledig. Dit kan niet ongedaan worden gemaakt.')
            ->action(function (array $arguments): void {
                if (QuizPalette::count() <= 1) {
                    Notification::make()
                        ->title('Kan niet verwijderen')
                        ->body('Er moet minstens één sfeerpalet overblijven, anders heeft de kleurvoorkeur-vraag niets meer om te tonen.')
                        ->danger()
                        ->send();

                    return;
                }

                QuizPalette::findOrFail($arguments['paletteId'])->delete();

                Notification::make()->title('Palet verwijderd')->success()->send();
            });
    }

    public function createColorAction(): Action
    {
        return Action::make('createColor')
            ->label('Kleur toevoegen')
            ->icon('heroicon-o-plus')
            ->form([
                TextInput::make('name')
                    ->label('Naam')
                    ->required()
                    ->maxLength(255),

                ColorPicker::make('hex')
                    ->label('Kleur')
                    ->required(),
            ])
            ->action(function (array $arguments, array $data): void {
                $paletteId = $arguments['paletteId'];
                $nextOrder = (QuizPaletteColor::where('quiz_palette_id', $paletteId)->max('sort_order') ?? 0) + 10;

                QuizPaletteColor::create([
                    'quiz_palette_id' => $paletteId,
                    'name' => $data['name'],
                    'hex' => $data['hex'],
                    'sort_order' => $nextOrder,
                ]);

                Notification::make()->title('Kleur toegevoegd')->success()->send();
            });
    }

    public function editColorAction(): Action
    {
        return Action::make('editColor')
            ->label('Kleur bewerken')
            ->modalHeading('Kleur bewerken')
            ->fillForm(fn (array $arguments): array => QuizPaletteColor::findOrFail($arguments['colorId'])->only(['name', 'hex']))
            ->form([
                TextInput::make('name')
                    ->label('Naam')
                    ->required()
                    ->maxLength(255),

                ColorPicker::make('hex')
                    ->label('Kleur')
                    ->required(),
            ])
            ->action(function (array $arguments, array $data): void {
                QuizPaletteColor::findOrFail($arguments['colorId'])->update($data);

                Notification::make()->title('Kleur bijgewerkt')->success()->send();
            });
    }

    public function deleteColorAction(): Action
    {
        return Action::make('deleteColor')
            ->label('Kleur verwijderen')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Kleur verwijderen?')
            ->action(function (array $arguments): void {
                $color = QuizPaletteColor::findOrFail($arguments['colorId']);

                if (QuizPaletteColor::where('quiz_palette_id', $color->quiz_palette_id)->count() <= 1) {
                    Notification::make()
                        ->title('Kan niet verwijderen')
                        ->body('Elk palet heeft minstens één kleur nodig.')
                        ->danger()
                        ->send();

                    return;
                }

                $color->delete();

                Notification::make()->title('Kleur verwijderd')->success()->send();
            });
    }

    /** @param  array<int, string>  $orderedIds  palet-id's in de nieuwe volgorde */
    public function reorderPalettes(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $paletteId) {
            QuizPalette::where('id', (int) $paletteId)->update(['sort_order' => ($index + 1) * 10]);
        }
    }

    /** @param  array<int, string>  $orderedIds  kleur-id's (binnen één palet) in de nieuwe volgorde */
    public function reorderColors(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $colorId) {
            QuizPaletteColor::where('id', (int) $colorId)->update(['sort_order' => ($index + 1) * 10]);
        }
    }
}
