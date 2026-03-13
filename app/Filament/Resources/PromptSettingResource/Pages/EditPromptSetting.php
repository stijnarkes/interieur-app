<?php

namespace App\Filament\Resources\PromptSettingResource\Pages;

use App\Filament\Resources\PromptSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPromptSetting extends EditRecord
{
    protected static string $resource = PromptSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Terug naar overzicht')
                ->icon('heroicon-o-arrow-left')
                ->url(static::getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Prompt opgeslagen';
    }
}
