<?php

namespace App\Filament\Resources\CfopResource\Pages;

use App\Filament\Resources\CfopResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCfop extends EditRecord
{
    protected static string $resource = CfopResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
