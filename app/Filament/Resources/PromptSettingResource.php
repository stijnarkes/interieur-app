<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromptSettingResource\Pages;
use App\Models\PromptSetting;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromptSettingResource extends Resource
{
    protected static ?string $model = PromptSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Prompt instellingen';

    protected static ?string $pluralModelLabel = 'Prompt instellingen';

    protected static ?string $modelLabel = 'Prompt instelling';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('key')
                    ->label('Sleutel')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('label')
                    ->label('Naam')
                    ->disabled()
                    ->dehydrated(false),

                Textarea::make('value')
                    ->label('Waarde')
                    ->rows(20)
                    ->columnSpanFull()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Sleutel')
                    ->searchable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('label')
                    ->label('Naam')
                    ->searchable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Bijgewerkt op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
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
            'index' => Pages\ListPromptSettings::route('/'),
            'edit'  => Pages\EditPromptSetting::route('/{record}/edit'),
        ];
    }
}
