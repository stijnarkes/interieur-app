<?php

namespace App\Filament\Resources\PartnerLinkResource\Pages;

use App\Filament\Resources\PartnerLinkResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPartnerLink extends ViewRecord
{
    protected static string $resource = PartnerLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
