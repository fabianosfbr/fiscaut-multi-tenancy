<?php

namespace App\Filament\Resources\ScheduledCommandResource\Pages;

use App\Filament\Resources\ScheduledCommandResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateScheduledCommand extends CreateRecord
{
    protected static string $resource = ScheduledCommandResource::class;



    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (in_array($data['preset'], ['every_minute', 'every_five_minutes', 'hourly'])) {
            $data['time'] = null;
        }
        return $data;
    }


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
