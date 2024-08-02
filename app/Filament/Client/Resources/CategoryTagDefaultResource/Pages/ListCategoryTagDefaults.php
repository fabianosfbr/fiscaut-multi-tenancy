<?php

namespace App\Filament\Client\Resources\CategoryTagDefaultResource\Pages;

use App\Filament\Client\Resources\CategoryTagDefaultResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategoryTagDefaults extends ListRecords
{
    protected static string $resource = CategoryTagDefaultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Criar categoria'),
        ];
    }
}
