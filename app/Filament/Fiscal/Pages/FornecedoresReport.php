<?php

namespace App\Filament\Fiscal\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant\NotaFiscalEletronica;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\DatePicker;

class FornecedoresReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $title = 'Relatório de Fornecedores';

    protected static ?string $navigationGroup = 'Relatórios';

    protected static ?int $navigationSort = 11;

    protected static string $view = 'filament.fiscal.pages.fornecedores-report';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function getQuery(): Builder
    {
        $organization = getOrganizationCached();

        return NotaFiscalEletronica::query()
            ->select([
                'nome_emitente',
                'cnpj_emitente',
                DB::raw('COUNT(*) as total_notas'),
                DB::raw('SUM(valor_total) as valor_total'),
                DB::raw('cnpj_emitente as id')
            ])
            ->where('cnpj_destinatario', $organization->cnpj)
            ->where('status_nota', 'AUTORIZADA')
            ->groupBy('nome_emitente', 'cnpj_emitente')
            ->orderByDesc('valor_total');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->columns([
                TextColumn::make('nome_emitente')
                    ->label('Razão Social')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cnpj_emitente')
                    ->label('CNPJ')
                    ->searchable(),
                TextColumn::make('total_notas')
                    ->label('Total de Notas')
                    ->sortable(),
                TextColumn::make('valor_total')
                    ->label('Valor Total')
                    ->money('BRL')
                    ->sortable(),
            ])
            ->defaultSort('valor_total', 'desc')
            ->filters([])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->actions([])
            ->bulkActions([]);
    }
}
