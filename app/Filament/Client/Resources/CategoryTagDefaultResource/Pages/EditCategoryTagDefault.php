<?php

namespace App\Filament\Client\Resources\CategoryTagDefaultResource\Pages;

use App\Filament\Client\Resources\CategoryTagDefaultResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategoryTagDefault extends EditRecord
{
    protected static string $resource = CategoryTagDefaultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->disabled(function () {
                    return $this->record->tags->count() > 0;
                })

        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
