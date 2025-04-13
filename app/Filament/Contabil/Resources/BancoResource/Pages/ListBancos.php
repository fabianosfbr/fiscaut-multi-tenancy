<?php

namespace App\Filament\Contabil\Resources\BancoResource\Pages;

use App\Filament\Contabil\Resources\BancoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBancos extends ListRecords
{
    protected static string $resource = BancoResource::class;

    protected static ?string $title = '';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
