<?php

namespace App\Filament\Pages;

use App\Filament\Resources\SubmissionResource;
use App\Models\Submission;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class PhotosPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = "Foto's";

    protected static ?string $title = "Geüploade kamerafoto's";

    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.pages.photos-page';

    public function getSubmissionsWithPhotos(): \Illuminate\Database\Eloquent\Collection
    {
        return Submission::where('has_room_photo', true)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getPhotoUrl(Submission $submission): string
    {
        if ($submission->room_photo_path && Storage::disk('public')->exists($submission->room_photo_path)) {
            return Storage::disk('public')->url($submission->room_photo_path);
        }

        return '';
    }

    public function getDetailUrl(Submission $submission): string
    {
        return SubmissionResource::getUrl('view', ['record' => $submission]);
    }
}
