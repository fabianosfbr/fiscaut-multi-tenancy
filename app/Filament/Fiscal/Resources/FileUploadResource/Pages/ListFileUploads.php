<?php

namespace App\Filament\Fiscal\Resources\FileUploadResource\Pages;

use App\Filament\Fiscal\Resources\FileUploadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFileUploads extends ListRecords
{
    protected static string $resource = FileUploadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo documento'),
        ];
    }

}
