<?php

namespace App\Filament\Fiscal\Resources\CteEntradaResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\Paginator;
use App\Filament\Fiscal\Resources\CteEntradaResource;

class ListCteEntradas extends ListRecords
{
    protected static string $resource = CteEntradaResource::class;

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
