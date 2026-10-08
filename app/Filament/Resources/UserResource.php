<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Auth\ResetPassword as ResetPasswordNotification;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

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

            // Bij het aanmaken geen wachtwoordveld meer: de nieuwe gebruiker krijgt een e-mail om
            // zelf een wachtwoord in te stellen (zie CreateAction::using() hieronder) — niemand
            // hoeft dus meer zelf een wachtwoord te verzinnen en veilig door te geven. Bij het
            // bewerken blijft handmatig een nieuw wachtwoord zetten mogelijk, voor het geval de
            // uitnodigingsmail nooit aankomt.
            TextInput::make('password')
                ->label('Wachtwoord')
                ->password()
                ->revealable()
                ->minLength(8)
                ->visible($context === 'edit')
                // Laat het wachtwoord ongewijzigd als het veld bij het bewerken leeg blijft —
                // de "hashed"-cast op User::password hasht een nieuwe waarde automatisch.
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->helperText('Laat leeg om het huidige wachtwoord te behouden.'),

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
                        ->helperText('Antwoordopties en sfeer-/materiaalfoto\'s.')
                        ->default(false),

                    Toggle::make('can_view_results')
                        ->label('Toegang tot Resultaten')
                        ->helperText('Inzendingen, leads, statistieken en exports.')
                        ->default(false),
                ]),
        ];
    }

    /**
     * Zelfde manier van versturen als Filament's eigen "wachtwoord vergeten"-pagina
     * (vendor/filament/filament/src/Pages/Auth/PasswordReset/RequestPasswordReset.php) — niet de
     * generieke Password::sendResetLink() van Laravel zelf, die zou linken naar een
     * password.reset-route die in deze app niet bestaat. Zo wordt dit precies hetzelfde
     * reset-wachtwoordscherm van het beheerpaneel als wanneer de gebruiker zelf op "Wachtwoord
     * vergeten?" had geklikt.
     *
     * Filament\Notifications\Auth\ResetPassword implementeert ShouldQueue — $user->notify() zou
     * 'm dus stil op de "jobs"-tabel laten staan, want deze app draait bewust zonder actieve
     * queue-worker (zie GenerateAndSendQuizResultPdfJob voor hetzelfde patroon). sendNow() dwingt
     * synchrone verzending af, net als overal elders in deze app.
     */
    private static function sendSetPasswordLink(User $user): void
    {
        Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(
            ['email' => $user->email],
            function (CanResetPassword $user, string $token): void {
                $notification = app(ResetPasswordNotification::class, ['token' => $token]);
                $notification->url = Filament::getResetPasswordUrl($token, $user);

                NotificationFacade::sendNow($user, $notification);
            },
        );
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
                    ->form(fn (): array => self::userForm('create'))
                    // Willekeurig, onbekend wachtwoord — niemand gebruikt dit ooit, de nieuwe
                    // gebruiker stelt via de e-mail hieronder zelf het echte wachtwoord in.
                    ->using(function (array $data): User {
                        $data['password'] = Hash::make(Str::random(40));

                        return User::create($data);
                    })
                    ->after(function (User $record): void {
                        self::sendSetPasswordLink($record);

                        Notification::make()
                            ->title('Uitnodiging verstuurd')
                            ->body("{$record->email} heeft een e-mail gekregen om een eigen wachtwoord in te stellen.")
                            ->success()
                            ->send();
                    }),
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
