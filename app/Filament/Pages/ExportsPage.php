<?php

namespace App\Filament\Pages;

use App\Models\Submission;
use Filament\Actions\Action;
use Filament\Pages\Page;

class ExportsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'Exporteren';

    protected static ?string $title = 'Exporteren';

    protected static ?int $navigationSort = 8;

    protected static string $view = 'filament.pages.exports-page';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_csv')
                ->label('Download leads als CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function () {
                    $leads = Submission::whereNotNull('email')
                        ->orderBy('created_at', 'desc')
                        ->get(['created_at', 'name', 'email', 'style', 'email_opt_in', 'has_room_photo', 'result_generated']);

                    $csv = "Datum,Naam,E-mail,Stijl,Marketing opt-in,Kamerafoto,Resultaat\n";
                    foreach ($leads as $lead) {
                        $csv .= implode(',', [
                            $lead->created_at->format('d-m-Y H:i'),
                            '"' . str_replace('"', '""', $lead->name ?? '') . '"',
                            '"' . str_replace('"', '""', $lead->email ?? '') . '"',
                            '"' . str_replace('"', '""', $lead->style) . '"',
                            $lead->email_opt_in ? 'Ja' : 'Nee',
                            $lead->has_room_photo ? 'Ja' : 'Nee',
                            $lead->result_generated ? 'Ja' : 'Nee',
                        ]) . "\n";
                    }

                    return response()->streamDownload(
                        fn () => print($csv),
                        'leads_' . now()->format('Y-m-d') . '.csv',
                        ['Content-Type' => 'text/csv']
                    );
                }),
        ];
    }
}
