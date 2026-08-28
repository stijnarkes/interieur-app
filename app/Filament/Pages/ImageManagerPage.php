<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\ManagesImageSlots;
use App\Models\QuizMaterial;
use App\Support\QuizImageManifest;
use App\Support\QuizStructure;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class ImageManagerPage extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use ManagesImageSlots;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = "Sfeer- & materiaalfoto's";

    protected static ?string $title = "Sfeer- & materiaalfoto's beheren";

    protected static ?string $navigationGroup = 'Quizbeheer';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.image-manager-page';

    public static function canAccess(): bool
    {
        return Auth::user()?->canManageQuiz() ?? false;
    }

    public function getSections(): array
    {
        return QuizImageManifest::atmosphereSections();
    }

    /** @return array<int, array{key: string, label: string, materials: \Illuminate\Support\Collection}> in vaste stijlvolgorde */
    public function getMaterialsByStyle(): array
    {
        $materials = QuizMaterial::query()->orderBy('sort_order')->get()->groupBy('style_key');

        return collect(QuizStructure::styleOptions())
            ->map(fn (string $label, string $key): array => [
                'key' => $key,
                'label' => $label,
                'materials' => $materials->get($key, collect()),
            ])
            ->values()
            ->all();
    }

    public function getUploadedCount(): int
    {
        return QuizImageManifest::uploadedCount($this->getSections()) + QuizMaterial::query()->get()->filter->hasImage()->count();
    }

    public function getTotalCount(): int
    {
        return QuizImageManifest::totalCount($this->getSections()) + QuizMaterial::query()->count();
    }

    public function createMaterialAction(): Action
    {
        return Action::make('createMaterial')
            ->label('Materiaal toevoegen')
            ->icon('heroicon-o-plus')
            ->modalHeading('Materiaal toevoegen')
            ->form([
                TextInput::make('name')
                    ->label('Naam')
                    ->required()
                    ->maxLength(255),

                FileUpload::make('image')
                    ->label('Afbeelding')
                    ->image()
                    ->required()
                    ->maxSize(10240)
                    ->disk('public')
                    ->directory('tmp-quiz-uploads')
                    ->visibility('private'),
            ])
            ->action(function (array $arguments, array $data): void {
                $styleKey = $arguments['styleKey'];
                $slug = Str::slug("{$styleKey}-{$data['name']}").'-'.Str::random(5);
                $nextOrder = (QuizMaterial::where('style_key', $styleKey)->max('sort_order') ?? 0) + 10;

                $material = QuizMaterial::create([
                    'style_key' => $styleKey,
                    'name' => $data['name'],
                    'filename' => "{$slug}.webp",
                    'sort_order' => $nextOrder,
                ]);

                try {
                    $material->storeImage($data['image']);
                } catch (Throwable $e) {
                    $material->delete();

                    Notification::make()->title('Uploaden mislukt')->body($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Materiaal toegevoegd')->success()->send();
            });
    }

    public function uploadMaterialAction(): Action
    {
        return Action::make('uploadMaterial')
            ->label('Uploaden')
            ->modalHeading(fn (array $arguments): string => QuizMaterial::find($arguments['materialId'])?->name ?? 'Afbeelding uploaden')
            ->modalSubmitActionLabel('Opslaan')
            ->form([
                FileUpload::make('image')
                    ->label('Afbeelding')
                    ->image()
                    ->required()
                    ->maxSize(10240)
                    ->disk('public')
                    ->directory('tmp-quiz-uploads')
                    ->visibility('private'),
            ])
            ->action(function (array $arguments, array $data): void {
                $material = QuizMaterial::findOrFail($arguments['materialId']);

                try {
                    $material->storeImage($data['image']);
                } catch (Throwable $e) {
                    Notification::make()->title('Uploaden mislukt')->body($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Afbeelding opgeslagen')->success()->send();
            });
    }

    public function deleteMaterialImageAction(): Action
    {
        return Action::make('deleteMaterialImage')
            ->label('Verwijder afbeelding')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Afbeelding verwijderen?')
            ->modalDescription('Hierna toont de app op deze plek weer de placeholder, totdat je een nieuwe foto uploadt.')
            ->action(function (array $arguments): void {
                QuizMaterial::findOrFail($arguments['materialId'])->deleteImage();

                Notification::make()->title('Afbeelding verwijderd')->success()->send();
            });
    }

    public function deleteMaterialAction(): Action
    {
        return Action::make('deleteMaterial')
            ->label('Materiaal verwijderen')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Materiaal verwijderen?')
            ->modalDescription('Dit materiaal verdwijnt volledig, inclusief de foto. Dit kan niet ongedaan worden gemaakt.')
            ->action(function (array $arguments): void {
                $material = QuizMaterial::findOrFail($arguments['materialId']);
                $material->deleteImage();
                $material->delete();

                Notification::make()->title('Materiaal verwijderd')->success()->send();
            });
    }
}
