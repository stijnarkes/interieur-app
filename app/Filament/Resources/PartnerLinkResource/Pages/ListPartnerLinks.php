<?php

namespace App\Filament\Resources\PartnerLinkResource\Pages;

use App\Filament\Resources\PartnerLinkResource;
use Filament\Resources\Pages\ListRecords;

class ListPartnerLinks extends ListRecords
{
    protected static string $resource = PartnerLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
