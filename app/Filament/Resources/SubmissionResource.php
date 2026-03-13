<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubmissionResource\Pages;
use App\Models\Submission;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationLabel = 'Inzendingen';

    protected static ?string $pluralModelLabel = 'Inzendingen';

    protected static ?string $modelLabel = 'Inzending';

    protected static ?int $navigationSort = 1;

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
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('style')
                    ->label('Stijl')
                    ->badge()
                    ->searchable(),

                Tables\Columns\IconColumn::make('has_room_photo')
                    ->label('Foto')
                    ->boolean(),

                Tables\Columns\IconColumn::make('result_generated')
                    ->label('Resultaat')
                    ->boolean(),

                Tables\Columns\TextColumn::make('email_status')
                    ->label('E-mail status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'sent'   => 'success',
                        'failed' => 'danger',
                        default  => 'gray',
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('email_sent_at')
                    ->label('E-mail verstuurd')
                    ->dateTime('d-m-Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('style')
                    ->label('Stijl')
                    ->options(fn (): array => Submission::query()
                        ->distinct()
                        ->orderBy('style')
                        ->pluck('style', 'style')
                        ->toArray()
                    ),

                TernaryFilter::make('email')
                    ->label('E-mail')
                    ->nullable()
                    ->trueLabel('Heeft e-mail')
                    ->falseLabel('Geen e-mail'),

                TernaryFilter::make('has_room_photo')
                    ->label('Kamerafoto')
                    ->trueLabel('Met foto')
                    ->falseLabel('Zonder foto'),

                TernaryFilter::make('result_generated')
                    ->label('Resultaat gegenereerd')
                    ->trueLabel('Gegenereerd')
                    ->falseLabel('Niet gegenereerd'),

                SelectFilter::make('email_status')
                    ->label('E-mail status')
                    ->options([
                        'sent'   => 'Verstuurd',
                        'failed' => 'Mislukt',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Gebruikersinformatie')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Naam')
                            ->placeholder('—'),
                        TextEntry::make('email')
                            ->label('E-mail')
                            ->placeholder('—'),
                        IconEntry::make('email_opt_in')
                            ->label('Marketing opt-in')
                            ->boolean(),
                        TextEntry::make('created_at')
                            ->label('Aangemaakt op')
                            ->dateTime('d-m-Y H:i'),
                        TextEntry::make('email_status')
                            ->label('E-mail status')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'sent'   => 'success',
                                'failed' => 'danger',
                                default  => 'gray',
                            })
                            ->placeholder('—'),
                        TextEntry::make('email_sent_at')
                            ->label('E-mail verstuurd op')
                            ->dateTime('d-m-Y H:i')
                            ->placeholder('—'),
                        TextEntry::make('email_error')
                            ->label('E-mail foutmelding')
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->visible(fn (Submission $record): bool => $record->email_status === 'failed'),
                    ])
                    ->columns(2),

                Section::make('Stijlkeuzes')
                    ->schema([
                        TextEntry::make('style')
                            ->label('Stijl')
                            ->badge(),
                        TextEntry::make('mood_words')
                            ->label('Sfeerwoorden')
                            ->placeholder('—'),
                        TextEntry::make('colors')
                            ->label('Kleuren')
                            ->placeholder('—'),
                        TextEntry::make('note')
                            ->label('Notitie')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Resultaat')
                    ->schema([
                        IconEntry::make('result_generated')
                            ->label('Resultaat gegenereerd')
                            ->boolean(),
                        TextEntry::make('result_id')
                            ->label('Result-ID')
                            ->placeholder('—')
                            ->copyable(),
                        IconEntry::make('moodboard_generated')
                            ->label('Moodboard gegenereerd')
                            ->boolean(),
                        IconEntry::make('room_preview_generated')
                            ->label('Kamerpreview gegenereerd')
                            ->boolean(),
                    ])
                    ->columns(2),

                Section::make('AI Advies')
                    ->visible(fn (Submission $record): bool => $record->result_generated)
                    ->schema([
                        TextEntry::make('advice_bullets')
                            ->label('Adviespunten')
                            ->listWithLineBreaks()
                            ->placeholder('—'),

                        TextEntry::make('palette')
                            ->label('Kleurenpalet')
                            ->formatStateUsing(fn ($state): string => is_array($state)
                                ? collect($state)->map(fn ($c) => "{$c['name']} ({$c['hex']})")->implode(', ')
                                : '—'
                            )
                            ->placeholder('—'),

                        TextEntry::make('materials')
                            ->label('Materialen')
                            ->formatStateUsing(fn ($state): string => is_array($state)
                                ? collect($state)->map(fn ($m) => $m['category'] . ': ' . implode(', ', $m['recommendations'] ?? []))->implode(' | ')
                                : '—'
                            )
                            ->placeholder('—'),

                        TextEntry::make('layout_tips')
                            ->label('Indelingstips')
                            ->listWithLineBreaks()
                            ->placeholder('—'),

                        TextEntry::make('product_ideas')
                            ->label('Productideeën')
                            ->formatStateUsing(fn ($state): string => is_array($state)
                                ? collect($state)->map(fn ($p) => "{$p['category']}: {$p['exampleSpecs']} ({$p['material']})")->implode(' | ')
                                : '—'
                            )
                            ->placeholder('—'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubmissions::route('/'),
            'view'  => Pages\ViewSubmission::route('/{record}'),
        ];
    }
}
