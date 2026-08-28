<?php

namespace App\Filament\Pages\Concerns;

use App\Support\QuizImageManifest;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Throwable;

/**
 * Gedeelde upload/verwijder-acties voor vaste-slot-afbeeldingen (zie QuizImageManifest) —
 * hergebruikt door zowel SitePhotosPage (startscherm/overgangen) als ImageManagerPage
 * (sfeerfoto's), zodat beide pagina's dezelfde upload/GD-webp-conversie/verwijderlogica delen.
 */
trait ManagesImageSlots
{
    public function imageUrl(string $folder, string $filename): ?string
    {
        return QuizImageManifest::url($folder, $filename);
    }

    public function uploadAction(): Action
    {
        return Action::make('upload')
            ->label('Uploaden')
            ->modalHeading(fn (array $arguments): string => $arguments['label'] ?? 'Afbeelding uploaden')
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
                try {
                    QuizImageManifest::store($arguments['folder'], $arguments['filename'], $data['image']);
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Uploaden mislukt')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Afbeelding opgeslagen')
                    ->success()
                    ->send();
            });
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label('Verwijderen')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Afbeelding verwijderen?')
            ->modalDescription('Hierna toont de app op deze plek weer de placeholder, totdat je een nieuwe foto uploadt.')
            ->action(function (array $arguments): void {
                QuizImageManifest::delete($arguments['folder'], $arguments['filename']);

                Notification::make()
                    ->title('Afbeelding verwijderd')
                    ->success()
                    ->send();
            });
    }
}
