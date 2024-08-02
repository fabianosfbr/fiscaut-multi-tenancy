<?php

namespace App\Filament\Client\Resources\CategoryTagResource\Pages;

use App\Filament\Client\Resources\CategoryTagResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateCategoryTag extends CreateRecord
{
    protected static string $resource = CategoryTagResource::class;


    public function getTitle(): string | Htmlable
    {
        return 'Criar Categoria';
    }
}
