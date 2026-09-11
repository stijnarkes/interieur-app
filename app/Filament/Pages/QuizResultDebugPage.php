<?php

namespace App\Filament\Pages;

use App\Models\QuizResult;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Read-only inzage in recente, server-berekende quizresultaten — inclusief de ruwe
 * scores/percentages/traits/ruimteprofielen en de bijbehorende AI-gegenereerde (of
 * teruggevallen) adviesteksten. Bedoeld om te debuggen waarom iemand een bepaalde uitslag kreeg,
 * zie het implementatieplan-vereiste "testresultaat inclusief berekening kunnen bekijken".
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
        return QuizResult::query()->with('generatedReports')->latest()->limit(50)->get();
    }

    public function viewResultAction(): Action
    {
        return Action::make('viewResult')
            ->label('Bekijken')
            ->modalHeading(fn (array $arguments): string => 'Resultaat '.$arguments['resultId'])
            ->modalWidth('3xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Sluiten')
            ->modalContent(fn (array $arguments) => view('filament.pages.partials.quiz-result-debug-detail', [
                'result' => QuizResult::with('generatedReports')->findOrFail($arguments['resultId']),
            ]));
    }
}
