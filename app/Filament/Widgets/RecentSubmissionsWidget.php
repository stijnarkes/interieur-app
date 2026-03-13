<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SubmissionResource;
use App\Models\Submission;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentSubmissionsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Recente inzendingen';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Submission::query()->latest()->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Datum')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Naam')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('style')
                    ->label('Stijl')
                    ->badge(),

                Tables\Columns\IconColumn::make('result_generated')
                    ->label('Resultaat')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Bekijk')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Submission $record): string => SubmissionResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
