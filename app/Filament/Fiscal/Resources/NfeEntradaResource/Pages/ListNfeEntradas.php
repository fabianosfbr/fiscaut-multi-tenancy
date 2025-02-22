<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Pages;

use App\Filament\Fiscal\Resources\NfeEntradaResource;
use App\Models\Tenant\Organization;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

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

    public function getTabs(): array
    {
        $organization = Organization::find(auth()->user()->last_organization_id);

        return [
            'propria' => Tab::make()
                ->label('Entrada de Terceiros')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('destinatario_cnpj', $organization->cnpj)
                    ->where('emitente_cnpj', '<>', $organization->cnpj)
                    ->where('tpNf', 1)
                    ->orderBy('data_emissao', 'DESC')),
            'terceiros' => Tab::make()
                ->label('Entrada Própria')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('emitente_cnpj', $organization->cnpj)->where('tpNf', 0)->orderBy('data_emissao', 'DESC')),
            'propria_terceiros' => Tab::make()
                ->label('Entrada Própria de Terceiros')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('destinatario_cnpj', $organization->cnpj)->where('emitente_cnpj', '<>', $organization->cnpj)->where('tpNf', 0)->orderBy('data_emissao', 'DESC')),

        ];
    }

    protected function paginateTableQuery(Builder $query): Paginator
    {
        return $query->fastPaginate(($this->getTableRecordsPerPage() === 'all') ? $query->count() : $this->getTableRecordsPerPage());
    }
}
