<?php

namespace App\Filament\Fiscal\Resources\NfeSaidaResource\Pages;

use App\Filament\Fiscal\Resources\NfeSaidaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNfeSaidas extends ListRecords
{
    protected static string $resource = NfeSaidaResource::class;

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getHeading(): string
    {
        return __('Notas Fiscais Eletrônicas');
    }
}
