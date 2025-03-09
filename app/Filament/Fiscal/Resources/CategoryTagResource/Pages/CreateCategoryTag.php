<?php

namespace App\Filament\Fiscal\Resources\CategoryTagResource\Pages;

use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Fiscal\Resources\CategoryTagResource;

class CreateCategoryTag extends CreateRecord
{
    protected static string $resource = CategoryTagResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_id'] = Auth::user()->last_organization_id;

        return $data;
    }
}
