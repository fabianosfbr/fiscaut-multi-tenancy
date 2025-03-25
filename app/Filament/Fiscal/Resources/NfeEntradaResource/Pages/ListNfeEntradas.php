<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Pages;

use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant\NotaFiscalEletronica;
use Illuminate\Contracts\Pagination\Paginator;
use App\Filament\Fiscal\Resources\NfeEntradaResource;


class ListNfeEntradas extends ListRecords
{
    protected static string $resource = NfeEntradaResource::class;

    protected ?string $maxContentWidth = 'full';



    public function getHeading(): string
    {
        return __('Notas Fiscais Eletrônicas');
    }

    public function getTabs(): array
    {
    
        return [
            'propria' => Tab::make()
                ->label('Entrada de Terceiros')
                ->query(function(){
                    return NotaFiscalEletronica::query()->entradasTerceiros(getOrganizationCached());
                }),
            'terceiros' => Tab::make()
                ->label('Entrada Própria')
                ->query(function(){
                    return NotaFiscalEletronica::query()->entradasProprias(getOrganizationCached());
                }),
            'propria_terceiros' => Tab::make()
                ->label('Entrada Própria de Terceiros')
                ->query(function(){
                    return NotaFiscalEletronica::query()->entradasPropriasTerceiros(getOrganizationCached());
                }),
          
        ];
    }




    protected function paginateTableQuery(Builder $query): Paginator
    {
        return $query->fastPaginate(($this->getTableRecordsPerPage() === 'all') ? $query->count() : $this->getTableRecordsPerPage());
    }
}
