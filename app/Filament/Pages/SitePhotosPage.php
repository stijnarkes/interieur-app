<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\ManagesImageSlots;
use App\Support\QuizImageManifest;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class SitePhotosPage extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use ManagesImageSlots;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Startscherm & overgangen';

    protected static ?string $title = 'Startscherm- en overgangsfoto\'s beheren';

    protected static ?string $navigationGroup = 'Quizbeheer';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.site-photos-page';

    public static function canAccess(): bool
    {
        return Auth::user()?->canManageQuiz() ?? false;
    }

    public function getSections(): array
    {
        return QuizImageManifest::pageSections();
    }

    public function getUploadedCount(): int
    {
        return QuizImageManifest::uploadedCount($this->getSections());
    }

    public function getTotalCount(): int
    {
        return QuizImageManifest::totalCount($this->getSections());
    }
}
