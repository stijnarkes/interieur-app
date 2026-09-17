<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnerLinkResource\Pages;
use App\Models\PartnerLink;
use App\Models\StyleProfile;
use Filament\Forms\Form;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * Alleen-lezen overzicht van de partnerfunctie ("Ontdek jullie gezamenlijke woonstijl") voor het
 * team: wie heeft wie uitgenodigd, wie heeft de test afgerond, en wat kwam er als gezamenlijk
 * advies uit — zelfde opzet als SubmissionResource (Inzendingen), maar dan voor partnerkoppelingen.
 * Nooit bewerkbaar/aan te maken vanuit de admin (dat gebeurt uitsluitend via de klant-quiz zelf).
 */
class PartnerLinkResource extends Resource
{
    protected static ?string $model = PartnerLink::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Partnerkoppelingen';

    protected static ?string $navigationGroup = 'Resultaten';

    protected static ?string $pluralModelLabel = 'Partnerkoppelingen';

    protected static ?string $modelLabel = 'Partnerkoppeling';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return Auth::user()?->canViewResults() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    private static function styleLabel(?string $key): ?string
    {
        return $key ? (StyleProfile::forStyle($key)?->label ?? $key) : null;
    }

    private static function statusLabel(?string $status): string
    {
        return match ($status) {
            PartnerLink::STATUS_WAITING => 'Wacht op partner',
            PartnerLink::STATUS_PARTNER_STARTED => 'Partner bezig',
            PartnerLink::STATUS_COMPLETED => 'Voltooid',
            PartnerLink::STATUS_EXPIRED => 'Verlopen',
            PartnerLink::STATUS_REVOKED => 'Ingetrokken',
            default => $status ?? '—',
        };
    }

    private static function statusColor(?string $status): string
    {
        return match ($status) {
            PartnerLink::STATUS_COMPLETED => 'success',
            PartnerLink::STATUS_PARTNER_STARTED => 'warning',
            PartnerLink::STATUS_WAITING => 'gray',
            PartnerLink::STATUS_EXPIRED, PartnerLink::STATUS_REVOKED => 'danger',
            default => 'gray',
        };
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
                    ->label('Uitgenodigd op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('initiator_name')
                    ->label('Initiator')
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('initiator_snapshot.primary_style')
                    ->label('Stijl initiator')
                    ->formatStateUsing(fn (?string $state): string => self::styleLabel($state) ?? '—')
                    ->badge(),

                Tables\Columns\TextColumn::make('partner_name')
                    ->label('Partner')
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('partner_snapshot.primary_style')
                    ->label('Stijl partner')
                    ->formatStateUsing(fn (?string $state): string => self::styleLabel($state) ?? '—')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                    ->color(fn (?string $state): string => self::statusColor($state)),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Voltooid op')
                    ->dateTime('d-m-Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        PartnerLink::STATUS_WAITING => 'Wacht op partner',
                        PartnerLink::STATUS_PARTNER_STARTED => 'Partner bezig',
                        PartnerLink::STATUS_COMPLETED => 'Voltooid',
                        PartnerLink::STATUS_EXPIRED => 'Verlopen',
                        PartnerLink::STATUS_REVOKED => 'Ingetrokken',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('view_pdf')
                    ->label('Bekijk PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (PartnerLink $record): string => route('admin.partner-links.pdf', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (PartnerLink $record): bool => $record->comparison?->status === \App\Models\PartnerComparison::STATUS_READY),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Koppeling')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                            ->color(fn (?string $state): string => self::statusColor($state)),
                        TextEntry::make('created_at')
                            ->label('Uitgenodigd op')
                            ->dateTime('d-m-Y H:i'),
                        TextEntry::make('completed_at')
                            ->label('Voltooid op')
                            ->dateTime('d-m-Y H:i')
                            ->placeholder('—'),
                        TextEntry::make('invite_expires_at')
                            ->label('Uitnodiging verloopt op')
                            ->dateTime('d-m-Y H:i'),
                        TextEntry::make('revoked_at')
                            ->label('Ingetrokken op')
                            ->dateTime('d-m-Y H:i')
                            ->placeholder('—')
                            ->visible(fn (PartnerLink $record): bool => filled($record->revoked_at)),
                    ])
                    ->columns(2),

                Section::make('Initiator')
                    ->schema([
                        TextEntry::make('initiator_name')
                            ->label('Naam')
                            ->placeholder('—'),
                        TextEntry::make('initiator_snapshot.primary_style')
                            ->label('Woonstijl')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::styleLabel($state) ?? '—'),
                        ViewEntry::make('initiator_snapshot.chosen_base_palette.colors')
                            ->label('Basiskleuren')
                            ->view('filament.infolists.color-palette-entry')
                            ->columnSpanFull(),
                        ViewEntry::make('initiator_snapshot.chosen_accent_colors')
                            ->label('Accentkleuren')
                            ->view('filament.infolists.color-palette-entry')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Partner')
                    ->visible(fn (PartnerLink $record): bool => filled($record->partner_snapshot) || filled($record->partner_name))
                    ->schema([
                        TextEntry::make('partner_name')
                            ->label('Naam')
                            ->placeholder('—'),
                        TextEntry::make('partner_snapshot.primary_style')
                            ->label('Woonstijl')
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(fn (?string $state): string => self::styleLabel($state) ?? '—'),
                        ViewEntry::make('partner_snapshot.chosen_base_palette.colors')
                            ->label('Basiskleuren')
                            ->view('filament.infolists.color-palette-entry')
                            ->columnSpanFull(),
                        ViewEntry::make('partner_snapshot.chosen_accent_colors')
                            ->label('Accentkleuren')
                            ->view('filament.infolists.color-palette-entry')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Gezamenlijke vergelijking')
                    ->visible(fn (PartnerLink $record): bool => (bool) $record->comparison)
                    ->schema([
                        ViewEntry::make('comparison.facts')
                            ->label('')
                            ->view('filament.infolists.partner-facts-entry')
                            ->columnSpanFull(),

                        TextEntry::make('comparison.suggestions.title')
                            ->label('Advies')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('comparison.suggestions.intro')
                            ->label('')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('comparison.suggestions.basisTip')
                            ->label('Basis-tip')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('comparison.suggestions.materialsTip')
                            ->label('Materialen-tip')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('comparison.suggestions.accentTip')
                            ->label('Accentkleuren-tip')
                            ->placeholder('—')
                            ->columnSpanFull(),

                        Actions::make([
                            InfolistAction::make('view_pdf')
                                ->label('Bekijk PDF')
                                ->icon('heroicon-o-document-text')
                                ->url(fn (PartnerLink $record): string => route('admin.partner-links.pdf', $record))
                                ->openUrlInNewTab(),

                            InfolistAction::make('download_pdf')
                                ->label('Download PDF')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->url(fn (PartnerLink $record): string => route('admin.partner-links.pdf.download', $record)),
                        ])
                            ->visible(fn (PartnerLink $record): bool => $record->comparison?->status === \App\Models\PartnerComparison::STATUS_READY)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartnerLinks::route('/'),
            'view' => Pages\ViewPartnerLink::route('/{record}'),
        ];
    }
}
