<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Models\Submission;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Leads';

    protected static ?string $pluralModelLabel = 'Leads';

    protected static ?string $modelLabel = 'Lead';

    protected static ?string $slug = 'leads';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('email');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Datum')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Naam')
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),

                Tables\Columns\TextColumn::make('style')
                    ->label('Stijl')
                    ->badge(),

                Tables\Columns\IconColumn::make('email_opt_in')
                    ->label('Marketing')
                    ->boolean(),

                Tables\Columns\IconColumn::make('has_room_photo')
                    ->label('Foto')
                    ->boolean(),

                Tables\Columns\IconColumn::make('result_generated')
                    ->label('Resultaat')
                    ->boolean(),
            ])
            ->filters([])
            ->headerActions([
                Action::make('export_csv')
                    ->label('Download CSV')
                    ->icon('heroicon-o-arrow-down-tray')
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
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Submission $record): string => SubmissionResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
        ];
    }
}
