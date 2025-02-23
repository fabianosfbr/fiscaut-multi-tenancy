<?php

namespace App\Filament\Fiscal\Resources\CategoryTagResource\Pages;

use App\Filament\Fiscal\Resources\CategoryTagResource;
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
