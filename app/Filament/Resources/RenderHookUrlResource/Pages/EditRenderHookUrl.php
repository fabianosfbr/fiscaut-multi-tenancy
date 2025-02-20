<?php

namespace App\Filament\Resources\RenderHookUrlResource\Pages;

use App\Filament\Resources\RenderHookUrlResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRenderHookUrl extends EditRecord
{
    protected static string $resource = RenderHookUrlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
