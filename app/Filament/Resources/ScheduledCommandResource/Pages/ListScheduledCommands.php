<?php

namespace App\Filament\Resources\ScheduledCommandResource\Pages;

use App\Filament\Resources\ScheduledCommandResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScheduledCommands extends ListRecords
{
    protected static string $resource = ScheduledCommandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
