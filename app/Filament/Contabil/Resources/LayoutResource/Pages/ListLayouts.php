<?php

namespace App\Filament\Contabil\Resources\LayoutResource\Pages;

use App\Filament\Contabil\Resources\LayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLayouts extends ListRecords
{
    protected static string $resource = LayoutResource::class;

    protected static ?string $title = '';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
