<?php

namespace App\Filament\Clusters\Profile\Pages;

use App\Filament\Clusters\Profile;
use Exception;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Model;

class BaseProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.clusters.profile.pages.base-profile';

    protected ?string $heading = '';

    protected ?string $subheading = '';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $cluster = Profile::class;

    public function getUser(): Model
    {
        $user = auth()->user();

        if (! $user instanceof Model) {
            throw new Exception('The authenticated user object must be an Eloquent model to allow the profile page to update it.');
        }

        return $user;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        return $record;
    }

    protected function getSavedNotification($title): ?Notification
    {
        if (blank($title)) {
            return null;
        }

        return Notification::make()
            ->success()
            ->title($title);
    }
}
