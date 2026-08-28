<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Gebruikers';

    protected static ?string $modelLabel = 'Gebruiker';

    protected static ?string $pluralModelLabel = 'Gebruikers';

    protected static ?string $slug = 'gebruikers';

    protected static ?string $navigationGroup = 'Instellingen';

    // Alleen volledig beheerders mogen gebruikers en hun rechten beheren — anders zou een
    // collega met beperkte rechten zichzelf hier alsnog meer toegang kunnen geven.
    public static function canAccess(): bool
    {
        return Auth::user()?->is_admin ?? false;
    }

    /** Gedeeld formulier voor zowel de "toevoegen"- als de "bewerken"-modal. */
    protected static function userForm(string $context, ?User $record = null): array
    {
        return [
            TextInput::make('name')
                ->label('Naam')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('E-mailadres')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),

            TextInput::make('password')
                ->label('Wachtwoord')
                ->password()
                ->revealable()
                ->minLength(8)
                ->required(fn (): bool => $context === 'create')
                // Laat het wachtwoord ongewijzigd als het veld bij het bewerken leeg blijft —
                // de "hashed"-cast op User::password hasht een nieuwe waarde automatisch.
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->helperText($context === 'create' ? null : 'Laat leeg om het huidige wachtwoord te behouden.'),

            Section::make('Rechten')
                ->schema([
                    Toggle::make('is_admin')
                        ->label('Volledig beheerder')
                        ->helperText('Heeft altijd overal toegang toe, inclusief dit gebruikersbeheer.')
                        ->default(false)
                        // Voorkomt dat je jezelf per ongeluk als beheerder afschrijft en jezelf
                        // buitensluit — hetzelfde idee als het verborgen "Verwijderen" hierboven.
                        ->disabled(fn (): bool => $record?->id === Auth::id()),

                    Toggle::make('can_manage_quiz')
                        ->label('Toegang tot Quizbeheer')
                        ->helperText('Antwoordopties, kleurvoorkeur en sfeer-/materiaalfoto\'s.')
                        ->default(false),

                    Toggle::make('can_view_results')
                        ->label('Toegang tot Resultaten')
                        ->helperText('Inzendingen, leads, statistieken en exports.')
                        ->default(false),
                ]),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Naam')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mailadres')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_admin')
                    ->label('Beheerder')
                    ->boolean(),

                Tables\Columns\IconColumn::make('can_manage_quiz')
                    ->label('Quizbeheer')
                    ->boolean(),

                Tables\Columns\IconColumn::make('can_view_results')
                    ->label('Resultaten')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Aangemaakt op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make()
                    ->label('Gebruiker toevoegen')
                    ->form(fn (): array => self::userForm('create')),
            ])
            ->actions([
                EditAction::make()
                    ->form(fn (User $record): array => self::userForm('edit', $record)),

                // Voorkomt dat je per ongeluk je eigen account verwijdert en jezelf buitensluit.
                DeleteAction::make()
                    ->visible(fn (User $record): bool => $record->id !== Auth::id()),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
        ];
    }
}
