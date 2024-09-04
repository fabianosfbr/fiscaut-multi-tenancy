<?php

namespace App\Filament\Fiscal\Resources\CategoryTagResource\Pages;

use App\Filament\Fiscal\Resources\CategoryTagResource;
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
