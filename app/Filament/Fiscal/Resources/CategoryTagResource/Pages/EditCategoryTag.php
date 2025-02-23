<?php

namespace App\Filament\Fiscal\Resources\CategoryTagResource\Pages;

use App\Filament\Fiscal\Resources\CategoryTagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategoryTag extends EditRecord
{
    protected static string $resource = CategoryTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
