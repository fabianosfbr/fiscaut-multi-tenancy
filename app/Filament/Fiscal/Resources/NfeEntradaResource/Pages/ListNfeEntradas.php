<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Pages;

use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\Paginator;
use App\Filament\Fiscal\Resources\NfeEntradaResource;

class ListNfeEntradas extends ListRecords
{
    protected static string $resource = NfeEntradaResource::class;

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getHeading(): string
    {
        return __('Notas Fiscais Eletrônicas');
    }


    protected function paginateTableQuery(Builder $query): Paginator
    {
        return $query->fastPaginate(($this->getTableRecordsPerPage() === 'all') ? $query->count() : $this->getTableRecordsPerPage());
    }
}
