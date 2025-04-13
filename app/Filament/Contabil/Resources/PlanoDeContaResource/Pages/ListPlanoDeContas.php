<?php

namespace App\Filament\Contabil\Resources\PlanoDeContaResource\Pages;

use App\Models\User;
use Filament\Actions;
use App\Models\PlanoDeConta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use App\Imports\PlanoDeContaImport;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Placeholder;

use EightyNine\ExcelImport\ExcelImportAction;
use Illuminate\Contracts\Pagination\Paginator;
use Konnco\FilamentImport\Actions\ImportField;
use Konnco\FilamentImport\Actions\ImportAction;
use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Filament\Contabil\Resources\PlanoDeContaResource;

class ListPlanoDeContas extends ListRecords
{
    protected static ?string $title = 'Plano de Contas';

    protected static string $resource = PlanoDeContaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }



    protected function paginateTableQuery(Builder $query): Paginator
    {
        return $query->fastPaginate(($this->getTableRecordsPerPage() === 'all') ? $query->count() : $this->getTableRecordsPerPage());
    }
}
