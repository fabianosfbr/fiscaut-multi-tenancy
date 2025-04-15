<?php

namespace App\Filament\Resources\CfopResource\Pages;

use App\Filament\Resources\CfopResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCfop extends CreateRecord
{
    protected static string $resource = CfopResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
