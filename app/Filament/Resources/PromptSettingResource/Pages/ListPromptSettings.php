<?php

namespace App\Filament\Resources\PromptSettingResource\Pages;

use App\Filament\Resources\PromptSettingResource;
use Filament\Resources\Pages\ListRecords;

class ListPromptSettings extends ListRecords
{
    protected static string $resource = PromptSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
