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
 * tweede stijl als "invloed" getoond wordt — zie de opdracht "scoring woonstijltest Boer
 * Staphorst": "Houd die grens instelbaar."
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
            ->fillForm(fn (): array => $this->getSettings()->only(['secondary_influence_max_gap']))
            ->form([
                TextInput::make('secondary_influence_max_gap')
                    ->label('Maximaal verschil in uitslagScore met de hoofdstijl')
                    ->helperText('De op-één-na-hoogste stijl wordt alleen als "invloed" getoond als haar uitslagScore positief is, hooguit dit verschil heeft met de hoofdstijl, én op minstens 2 verschillende vragen boven het vraaggemiddelde van die stijl scoorde. Standaard 0,5.')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $this->getSettings()->update($data);

                Notification::make()->title('Instelling opgeslagen')->success()->send();
            });
    }

    /**
     * Enige aan/uit-schakelaar voor de partnerfunctie ("Ontdek jullie gezamenlijke woonstijl") —
     * zie App\Http\Middleware\EnsurePartnerFeatureEnabled. Staat uit totdat hier expliciet
     * aangezet, zodat de bestaande individuele quiz tot activering onveranderd blijft.
     */
    public function togglePartnerFeatureAction(): Action
    {
        return Action::make('togglePartnerFeature')
            ->label(fn (): string => $this->getSettings()->partner_feature_enabled ? 'Uitschakelen' : 'Inschakelen')
            ->color(fn (): string => $this->getSettings()->partner_feature_enabled ? 'danger' : 'success')
            ->requiresConfirmation()
            ->action(function (): void {
                $settings = $this->getSettings();
                $settings->update(['partner_feature_enabled' => ! $settings->partner_feature_enabled]);

                Notification::make()->title('Instelling opgeslagen')->success()->send();
            });
    }
}
