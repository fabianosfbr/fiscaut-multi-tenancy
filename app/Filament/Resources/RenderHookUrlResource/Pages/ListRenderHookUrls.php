<?php

namespace App\Filament\Resources\RenderHookUrlResource\Pages;

use App\Filament\Resources\RenderHookUrlResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRenderHookUrls extends ListRecords
{
    protected static string $resource = RenderHookUrlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
