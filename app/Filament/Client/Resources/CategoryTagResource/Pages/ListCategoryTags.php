<?php

namespace App\Filament\Client\Resources\CategoryTagResource\Pages;

use App\Filament\Client\Resources\CategoryTagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategoryTags extends ListRecords
{
    protected static string $resource = CategoryTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
