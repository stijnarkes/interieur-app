<?php

namespace App\Filament\Pages;

use App\Models\QuizSetting;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Beheert de drempel die QuizScoringService::determineResult() gebruikt om te bepalen of een
 * tweede stijl als "invloed" getoond wordt — zie de opdracht "vereenvoudiging woonstijltest":
 * "Maak deze grens eenvoudig aanpasbaar/configureerbaar."
 */
class QuizSettingsPage extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Uitslag-instellingen';

    protected static ?string $title = 'Uitslag-instellingen';

    protected static ?string $slug = 'quiz-instellingen';

    protected static ?string $navigationGroup = 'Quizbeheer';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.quiz-settings-page';

    public static function canAccess(): bool
    {
        return Auth::user()?->canManageQuiz() ?? false;
    }

    public function getSettings(): QuizSetting
    {
        return QuizSetting::current();
    }

    public function editThresholdAction(): Action
    {
        return Action::make('editThreshold')
            ->label('Drempel bewerken')
            ->modalHeading('Drempel voor de tweede invloed')
            ->fillForm(fn (): array => $this->getSettings()->only(['secondary_influence_ratio']))
            ->form([
                TextInput::make('secondary_influence_ratio')
                    ->label('Minimaal percentage van de basisscore')
                    ->helperText('De op-één-na-hoogste stijl wordt alleen als "invloed" getoond als haar score minstens dit percentage van de basisstijl haalt, én in minstens 2 verschillende vragen punten kreeg. Standaard 70.')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->suffix('%')
                    ->required(),
            ])
            ->action(function (array $data): void {
                $this->getSettings()->update($data);

                Notification::make()->title('Instelling opgeslagen')->success()->send();
            });
    }
}
