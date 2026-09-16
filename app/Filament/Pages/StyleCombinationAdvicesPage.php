<?php

namespace App\Filament\Pages;

use App\Models\StyleCombinationAdvice;
use App\Support\QuizStructure;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Beheert de 21 redactionele stijlcombinatie-adviezen voor de partnerfunctie ("Ontdek jullie
 * gezamenlijke woonstijl", zie App\Models\StyleCombinationAdvice) — vaste set (6 stijlen -> 15
 * paren + 6 zelfde-stijl-combinaties, zie StyleCombinationAdviceSeeder), dus anders dan
 * BasePalettesPage geen toevoegen/verwijderen/herordenen: alleen bewerken en publiceren. Een
 * conceptrecord wordt nooit aan een bezoeker getoond (zie PartnerComparisonService), dus deze
 * pagina is de enige plek waar dat verandert.
 */
class StyleCombinationAdvicesPage extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Stijlcombinaties';

    protected static ?string $title = 'Stijlcombinaties';

    protected static ?string $slug = 'stijlcombinaties';

    protected static ?string $navigationGroup = 'Quizbeheer';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.style-combination-advices-page';

    public static function canAccess(): bool
    {
        return Auth::user()?->canManageQuiz() ?? false;
    }

    /** @return array<string, \Illuminate\Support\Collection<int, StyleCombinationAdvice>> per stijl-A, in vaste QuizStructure-volgorde */
    public function getAdvicesByStyle(): array
    {
        $grouped = StyleCombinationAdvice::query()->orderBy('style_key_b')->get()->groupBy('style_key_a');

        return collect(QuizStructure::styleOptions())
            ->mapWithKeys(fn (string $label, string $styleKey) => [$styleKey => $grouped->get($styleKey, collect())])
            ->all();
    }

    public function editAdviceAction(): Action
    {
        return Action::make('editAdvice')
            ->label('Bewerken')
            ->modalHeading('Stijlcombinatie bewerken')
            ->fillForm(fn (array $arguments): array => StyleCombinationAdvice::findOrFail($arguments['adviceId'])
                ->only(['title', 'intro', 'basis_tip', 'materials_tip', 'accent_tip', 'base_palette_style_key']))
            ->form([
                TextInput::make('title')->label('Titel')->required()->maxLength(255),
                Textarea::make('intro')->label('Introductietekst')->rows(3)->required(),
                Textarea::make('basis_tip')->label('Tip voor de basiskleuren')->rows(3)->required(),
                Textarea::make('materials_tip')->label('Tip voor materialen')->rows(3)->required(),
                Textarea::make('accent_tip')->label('Tip voor accentkleuren')->rows(3)->required(),
                Select::make('base_palette_style_key')
                    ->label('Optionele basispalet-verwijzing')
                    ->helperText('Verwijst naar de basispaletten van één specifieke stijl, als dit paar daar bewust naar toe leidt.')
                    ->options(QuizStructure::styleOptions())
                    ->native(false),
            ])
            ->action(function (array $arguments, array $data): void {
                $advice = StyleCombinationAdvice::findOrFail($arguments['adviceId']);
                $advice->update([...$data, 'version' => $advice->version + 1]);

                Notification::make()->title('Stijlcombinatie bijgewerkt')->success()->send();
            });
    }

    public function togglePublished(int $adviceId): void
    {
        $advice = StyleCombinationAdvice::findOrFail($adviceId);
        $advice->update(['status' => $advice->status === 'published' ? 'concept' : 'published']);
    }
}
