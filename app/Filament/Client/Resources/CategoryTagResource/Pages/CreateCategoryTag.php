<?php

namespace App\Filament\Client\Resources\CategoryTagResource\Pages;

use App\Filament\Client\Resources\CategoryTagResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCategoryTag extends CreateRecord
{
    protected static string $resource = CategoryTagResource::class;

    protected static bool $canCreateAnother = false;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_id'] = auth()->user()->last_organization_id;
        return $data;
    }
}
