<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubmissionResource\Pages;
use App\Models\Submission;
use App\Support\QuizAnswerFormatter;
use App\Support\QuizStructure;
use Filament\Forms\Form;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationLabel = 'Inzendingen';

    protected static ?string $navigationGroup = 'Resultaten';

    protected static ?string $pluralModelLabel = 'Inzendingen';

    protected static ?string $modelLabel = 'Inzending';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return Auth::user()?->canViewResults() ?? false;
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
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('style')
                    ->label('Stijl')
                    ->badge()
                    ->searchable(),

                Tables\Columns\IconColumn::make('result_generated')
                    ->label('Resultaat')
                    ->boolean(),

                Tables\Columns\TextColumn::make('email_status')
                    ->label('E-mail status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
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
                    // Submission.style slaat het label op (zie QuizLeadController), dus de
                    // sleutel/waarde van deze opties moet ook het label zijn, niet de
                    // stijl-key. Vaste lijst i.p.v. een distinct()-query op elk paginabezoek —
                    // de mogelijke stijlen liggen toch al vast in QuizStructure.
                    ->options(fn (): array => array_combine(
                        array_values(QuizStructure::styleOptions()),
                        array_values(QuizStructure::styleOptions()),
                    )),

                TernaryFilter::make('email')
                    ->label('E-mail')
                    ->nullable()
                    ->trueLabel('Heeft e-mail')
                    ->falseLabel('Geen e-mail'),

                TernaryFilter::make('result_generated')
                    ->label('Resultaat gegenereerd')
                    ->trueLabel('Gegenereerd')
                    ->falseLabel('Niet gegenereerd'),

                SelectFilter::make('email_status')
                    ->label('E-mail status')
                    ->options([
                        'sent' => 'Verstuurd',
                        'failed' => 'Mislukt',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('view_pdf')
                    ->label('Bekijk PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Submission $record): string => route('admin.submissions.pdf', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Submission $record): bool => filled($record->pdf_path)),

                Tables\Actions\Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Submission $record): string => route('admin.submissions.pdf.download', $record))
                    ->visible(fn (Submission $record): bool => filled($record->pdf_path)),

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
                                'sent' => 'success',
                                'failed' => 'danger',
                                default => 'gray',
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

                Section::make('Stijltest resultaat')
                    ->schema([
                        TextEntry::make('quiz_result.resultName')
                            ->label('Woonstijl')
                            ->badge()
                            ->size(TextEntry\TextEntrySize::Large),

                        TextEntry::make('quiz_result.description')
                            ->label('Omschrijving')
                            ->placeholder('—')
                            ->columnSpanFull(),

                        TextEntry::make('primary_style_display')
                            ->label('Primaire stijl')
                            ->badge()
                            ->color('success')
                            ->placeholder('—')
                            ->getStateUsing(fn (Submission $record): ?string => self::formatTopStyle($record, 0)),

                        TextEntry::make('secondary_style_display')
                            ->label('Secundaire stijl')
                            ->badge()
                            ->color('gray')
                            ->placeholder('—')
                            ->getStateUsing(fn (Submission $record): ?string => self::formatTopStyle($record, 1)),

                        TextEntry::make('tertiary_style_display')
                            ->label('Tertiaire stijl')
                            ->badge()
                            ->color('gray')
                            ->placeholder('—')
                            ->getStateUsing(fn (Submission $record): ?string => self::formatTopStyle($record, 2)),

                        TextEntry::make('traits')
                            ->label('Kenmerken')
                            ->getStateUsing(fn (Submission $record): string => collect($record->quiz_result['traits'] ?? [])->implode(' • ') ?: '—'
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Kleurresultaat')
                    ->visible(fn (Submission $record): bool => ! empty($record->quiz_result['personalPalette']))
                    ->schema([
                        ViewEntry::make('quiz_result.personalPalette')
                            ->label('')
                            ->view('filament.infolists.color-palette-entry')
                            ->columnSpanFull(),
                    ]),

                Section::make('Moodboard')
                    ->visible(fn (Submission $record): bool => ! empty($record->quiz_result['moodboard']))
                    ->schema([
                        ViewEntry::make('quiz_result.moodboard')
                            ->label('')
                            ->view('filament.infolists.moodboard-entry')
                            ->columnSpanFull(),
                    ]),

                Section::make('Gekozen antwoorden')
                    ->schema([
                        KeyValueEntry::make('quiz_answers')
                            ->label('')
                            ->keyLabel('Stap')
                            ->valueLabel('Gekozen stijl')
                            ->getStateUsing(fn (Submission $record): array => QuizAnswerFormatter::format($record->quiz_answers))
                            ->columnSpanFull(),
                    ]),

                Section::make('Resultaat')
                    ->schema([
                        IconEntry::make('result_generated')
                            ->label('Resultaat gegenereerd')
                            ->boolean(),
                        TextEntry::make('result_id')
                            ->label('Result-ID')
                            ->placeholder('—')
                            ->copyable(),

                        Actions::make([
                            InfolistAction::make('view_pdf')
                                ->label('Bekijk PDF')
                                ->icon('heroicon-o-document-text')
                                ->url(fn (Submission $record): string => route('admin.submissions.pdf', $record))
                                ->openUrlInNewTab(),

                            InfolistAction::make('download_pdf')
                                ->label('Download PDF')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->url(fn (Submission $record): string => route('admin.submissions.pdf.download', $record)),
                        ])
                            ->visible(fn (Submission $record): bool => filled($record->pdf_path))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    private static function formatTopStyle(Submission $record, int $index): ?string
    {
        $style = ($record->quiz_result['topStyles'] ?? [])[$index] ?? null;

        if (! $style) {
            return null;
        }

        return "{$style['label']} ({$style['percentage']}%)";
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubmissions::route('/'),
            'view' => Pages\ViewSubmission::route('/{record}'),
        ];
    }
}
