<?php

namespace App\Filament\Client\Resources\CategoryTagDefaultResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Client\Resources\CategoryTagDefaultResource;

class CreateCategoryTagDefault extends CreateRecord
{
    protected static string $resource = CategoryTagDefaultResource::class;

    public function getTitle(): string | Htmlable
    {
        return 'Criar Categoria';
    }
}
