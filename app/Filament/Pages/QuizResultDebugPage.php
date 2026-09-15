<?php

namespace App\Filament\Pages;

use App\Models\QuizResult;
use App\Services\QuizScoringService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Read-only inzage in recente, server-berekende quizresultaten — de ruwe punten per stijl en of
 * de tweede stijl wel/niet aan de invloed-eis voldeed. Bedoeld om te debuggen waarom iemand een
 * bepaalde uitslag kreeg, zonder de bezoeker zelf ooit puntentabellen te tonen.
 */
class QuizResultDebugPage extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';

    protected static ?string $navigationLabel = 'Resultaten debuggen';

    protected static ?string $title = 'Quizresultaten debuggen';

    protected static ?string $slug = 'quiz-resultaten-debug';

    protected static ?string $navigationGroup = 'Resultaten';

    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.pages.quiz-result-debug-page';

    public static function canAccess(): bool
    {
        return Auth::user()?->canManageQuiz() ?? false;
    }

    /** @return \Illuminate\Support\Collection<int, QuizResult> de 50 meest recente resultaten */
    public function getResults()
    {
        return QuizResult::query()->latest()->limit(50)->get();
    }

    public function viewResultAction(): Action
    {
        return Action::make('viewResult')
            ->label('Bekijken')
            ->modalHeading(fn (array $arguments): string => 'Resultaat '.$arguments['resultId'])
            ->modalWidth('3xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Sluiten')
            ->modalContent(function (array $arguments) {
                $result = QuizResult::findOrFail($arguments['resultId']);

                return view('filament.pages.partials.quiz-result-debug-detail', [
                    'result' => $result,
                    'explanation' => app(QuizScoringService::class)->explain($result->answers),
                ]);
            });
    }
}
