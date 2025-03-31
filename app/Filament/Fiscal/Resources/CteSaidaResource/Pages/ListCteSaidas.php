<?php

namespace App\Filament\Fiscal\Resources\CteSaidaResource\Pages;

use App\Filament\Fiscal\Resources\CteSaidaResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\Paginator;
use Filament\Resources\Pages\ListRecords;

class ListCteSaidas extends ListRecords
{
    protected static string $resource = CteSaidaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getHeading(): string
    {
        return __('Conhecimento Transporte Eletrônico');
    }

    protected function paginateTableQuery(Builder $query): Paginator
    {
        return $query->fastPaginate(($this->getTableRecordsPerPage() === 'all') ? $query->count() : $this->getTableRecordsPerPage());
    }
}
