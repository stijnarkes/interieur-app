<?php

namespace App\Filament\Pages;

use App\Models\StyleProfile;
use App\Support\QuizImageManifest;
use App\Support\QuizStructure;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Beheert de rijke, per-stijl inhoud (kernomschrijving, kleuren, materialen, meubeladvies,
 * interieurrecept, "wat past minder goed") die vroeger hardcoded in resources/js/quiz/
 * styleProfiles.js stond. Precies 8 vaste rijen (QuizStructure::STYLES): geen create/delete,
 * alleen bewerken. Dit is de enige bron waar QuizResultTextComposer en de PDF uit putten.
 */
class StyleProfilesPage extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationLabel = 'Stijlprofielen';

    protected static ?string $title = 'Stijlprofielen';

    protected static ?string $slug = 'stijlprofielen';

    protected static ?string $navigationGroup = 'Quizbeheer';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.style-profiles-page';

    public static function canAccess(): bool
    {
        return Auth::user()?->canManageQuiz() ?? false;
    }

    /** @return array<int, StyleProfile> in de vaste QuizStructure::STYLES-volgorde */
    public function getProfiles(): array
    {
        $profiles = StyleProfile::query()->get()->keyBy('style_key');

        return collect(QuizStructure::styleKeys())
            ->map(fn (string $key) => $profiles->get($key))
            ->filter()
            ->values()
            ->all();
    }

    public function editProfileAction(): Action
    {
        return Action::make('editProfile')
            ->label('Bewerken')
            ->modalHeading(fn (array $arguments): string => 'Stijlprofiel bewerken: '.(StyleProfile::find($arguments['profileId'])?->label ?? ''))
            ->modalWidth('4xl')
            ->fillForm(function (array $arguments): array {
                $profile = StyleProfile::findOrFail($arguments['profileId']);

                return [
                    ...$profile->toArray(),
                    'furniture_intro' => $profile->furniture_shapes['intro'] ?? null,
                    'furniture_items' => $profile->furniture_shapes['items'] ?? [],
                ];
            })
            ->form([
                FormSection::make('Basis')
                    ->schema([
                        TextInput::make('label')->label('Naam')->required()->maxLength(255),
                        TextInput::make('subtitle')->label('Ondertitel')->maxLength(255),
                        Textarea::make('long_description')->label('Persoonlijke introductie')->rows(3),
                        Textarea::make('traits_intro')->label('Intro boven kenmerken')->rows(2),
                        TagsInput::make('core_traits')
                            ->label('Kenmerken')
                            ->helperText('Losse trefwoorden, bv. "Warme, rustige kleuren" — Enter om toe te voegen.'),
                        FileUpload::make('hero_image_upload')
                            ->label('Sfeerfoto')
                            ->helperText('Laat leeg om de huidige sfeerfoto te behouden.')
                            ->image()
                            ->disk('public')
                            ->directory('tmp-quiz-uploads')
                            ->visibility('private')
                            ->dehydrated(false),
                    ]),

                FormSection::make('Kleuren')
                    ->collapsed()
                    ->schema([
                        Repeater::make('base_colors')
                            ->label('Basiskleuren')
                            ->helperText('Dit zijn de enige kleuren die de bezoeker te zien krijgt onder "Kleuren ter inspiratie" — houd dit bewust neutraal/veilig, geen specifieke accentkleur.')
                            ->schema([
                                TextInput::make('name')->label('Naam')->required(),
                                TextInput::make('hex')->label('Hexcode')->required(),
                            ])
                            ->columns(2),
                        Repeater::make('accent_colors')
                            ->label('Accentkleuren')
                            ->helperText('Alleen intern/voor de styliste — deze worden niet aan de bezoeker getoond, omdat we niet weten welke accentkleur bij deze specifieke bezoeker past.')
                            ->schema([
                                TextInput::make('name')->label('Naam')->required(),
                                TextInput::make('hex')->label('Hexcode')->required(),
                            ])
                            ->columns(2),
                        Textarea::make('color_tip')->label('Kleuradvies-tip')->rows(2),
                    ]),

                FormSection::make('Materialen')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('materials_image_upload')
                            ->label('Materialenfoto')
                            ->helperText('Eén samengestelde foto met de materialen hieronder (bv. een moodboard-collage). Laat leeg om de huidige foto te behouden.')
                            ->image()
                            ->disk('public')
                            ->directory('tmp-quiz-uploads')
                            ->visibility('private')
                            ->dehydrated(false),
                        TagsInput::make('materials')
                            ->label('Materialen op de foto')
                            ->helperText('Namen van de materialen die op de foto hierboven te zien zijn — Enter om toe te voegen.'),
                        Textarea::make('materials_tip')->label('Materialen-tip')->rows(2),
                    ]),

                FormSection::make('Meubeladvies')
                    ->collapsed()
                    ->schema([
                        Textarea::make('furniture_intro')->label('Intro')->rows(2),
                        TagsInput::make('furniture_items')->label('Meubelkenmerken'),
                    ]),

                FormSection::make('Advies per rol')
                    ->collapsed()
                    ->schema([
                        Textarea::make('advice_primary')->label('Als primaire stijl (basis)')->rows(3),
                        Textarea::make('advice_secondary')->label('Als secundaire invloed')->rows(3),
                        Textarea::make('advice_tertiary')->label('Als tertiair accent')->rows(3),
                    ]),

                FormSection::make('Interieurrecept')
                    ->collapsed()
                    ->schema([
                        Repeater::make('recipe')
                            ->label('Recept')
                            ->schema([
                                TextInput::make('label')->label('Label')->required(),
                                TextInput::make('value')->label('Waarde')->required(),
                            ])
                            ->columns(2),
                    ]),

                FormSection::make('Wat past (niet)')
                    ->collapsed()
                    ->schema([
                        TagsInput::make('wat_past_goed')->label('Wat goed past'),
                        TagsInput::make('wat_past_minder_goed')->label('Wat minder goed past'),
                    ]),
            ])
            ->action(function (array $arguments, array $data): void {
                $profile = StyleProfile::findOrFail($arguments['profileId']);

                if (! empty($data['hero_image_upload'])) {
                    $data['hero_image'] = $this->storeUploadedImage($data['hero_image_upload'], "atmosphere/{$profile->slug}");
                }
                unset($data['hero_image_upload']);

                if (! empty($data['materials_image_upload'])) {
                    $data['materials_image'] = $this->storeUploadedImage($data['materials_image_upload'], "materials-board/{$profile->slug}");
                }
                unset($data['materials_image_upload']);

                $data['furniture_shapes'] = [
                    'intro' => $data['furniture_intro'] ?? null,
                    'items' => $data['furniture_items'] ?? [],
                ];
                unset($data['furniture_intro'], $data['furniture_items']);

                $profile->update($data);

                Notification::make()->title('Stijlprofiel bijgewerkt')->success()->send();
            });
    }

    private function storeUploadedImage(string $uploadedDiskPath, string $relativePath): string
    {
        $path = "images/interior/{$relativePath}.webp";
        QuizImageManifest::storeAtPath($path, $uploadedDiskPath, 1600);

        return "/{$path}";
    }
}
