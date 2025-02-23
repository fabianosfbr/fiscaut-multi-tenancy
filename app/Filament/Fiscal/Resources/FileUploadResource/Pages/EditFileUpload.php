<?php

namespace App\Filament\Fiscal\Resources\FileUploadResource\Pages;

use App\Filament\Fiscal\Resources\FileUploadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFileUpload extends EditRecord
{
    protected static string $resource = FileUploadResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
