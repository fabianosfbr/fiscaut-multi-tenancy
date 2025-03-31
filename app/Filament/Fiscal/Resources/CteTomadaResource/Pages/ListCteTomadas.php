<?php

namespace App\Filament\Fiscal\Resources\CteTomadaResource\Pages;

use App\Filament\Fiscal\Resources\CteTomadaResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\Paginator;
use Filament\Resources\Pages\ListRecords;

class ListCteTomadas extends ListRecords
{
    protected static string $resource = CteTomadaResource::class;

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
