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
 * Beheert de drie procentpunt-marges die QuizScoringService::determineRanking() gebruikt om een
 * duidelijke winnaar / twee bijna-gelijke stijlen / drie bijna-gelijke stijlen / een tegenstrijdige
 * verdeling te onderscheiden — zie het implementatieplan: "exacte drempelwaarden moeten in
 * configuratie worden gezet, zodat deze later zonder codewijziging zijn bij te stellen."
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

    public function editThresholdsAction(): Action
    {
        return Action::make('editThresholds')
            ->label('Drempelwaarden bewerken')
            ->modalHeading('Drempelwaarden voor de uitslag')
            ->fillForm(fn (): array => $this->getSettings()->only(['primary_dominant_margin', 'close_pair_margin', 'close_triple_margin']))
            ->form([
                TextInput::make('primary_dominant_margin')
                    ->label('Marge voor een duidelijke winnaar')
                    ->helperText('Percentagepunt-verschil met de nummer 2 waarboven de primaire stijl als sterk dominant geldt. Standaard 15.')
                    ->numeric()->minValue(1)->maxValue(100)->required(),

                TextInput::make('close_pair_margin')
                    ->label('Marge voor "twee bijna-gelijke stijlen"')
                    ->helperText('Als het verschil tussen nummer 1 en 2 kleiner is dan dit, en het verschil met nummer 3 juist groter, wordt het een gemengd profiel van twee stijlen. Standaard 8.')
                    ->numeric()->minValue(1)->maxValue(100)->required(),

                TextInput::make('close_triple_margin')
                    ->label('Marge voor "drie bijna-gelijke stijlen"')
                    ->helperText('Als de verschillen tussen nummer 1, 2 én 3 allemaal kleiner zijn dan dit, wordt het een drieweg-mix. Standaard 5.')
                    ->numeric()->minValue(1)->maxValue(100)->required(),
            ])
            ->action(function (array $data): void {
                $this->getSettings()->update($data);

                Notification::make()->title('Instellingen opgeslagen')->success()->send();
            });
    }
}
