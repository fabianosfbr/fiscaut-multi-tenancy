<?php

namespace App\Filament\Ged\Resources\DocumentOCRResource\Pages;

use App\Filament\Ged\Resources\DocumentOCRResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocumentOCR extends EditRecord
{
    protected static string $resource = DocumentOCRResource::class;

    protected function getHeaderActions(): array
    {
            return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
