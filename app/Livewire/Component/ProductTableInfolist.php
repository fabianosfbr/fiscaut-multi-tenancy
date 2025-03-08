<?php

namespace App\Livewire\Component;

use Livewire\Component;
use App\Models\Tenant\NotaFiscalEletronica;
use App\Models\Tenant\NotaFiscalEletronicaItem;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductTableInfolist extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;


    public NotaFiscalEletronica $record;

    public function mount(NotaFiscalEletronica $record)
    {
        $this->record = $record;
    }

    public function table(Table $table): Table
    {
       
        return $table
            ->query(NotaFiscalEletronicaItem::query()->where('nfe_id', $this->record->id))
            ->paginated(false)
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código'),
                TextColumn::make('descricao')
                    ->label('Descrição')
                    ->limit(20)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getListLimit()) {
                            return null;
                        }

                        // Only render the tooltip if the column contents exceeds the length limit.
                        return $state;
                    }),
                TextColumn::make('ncm')
                    ->label('NCM'),
                TextColumn::make('cfop')
                    ->label('CFOP'),
                TextColumn::make('quantidade')
                    ->label('Qtde')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    ),
                TextColumn::make('unidade')
                    ->label('Unidade')
                    ->alignCenter(),
                TextColumn::make('valor_unitario')
                    ->label('Valor Unit')
                    ->money('BRL'),

                TextColumn::make('base_calculo_icms')
                    ->label('B.ICMS')
                    ->money('BRL'),
                TextColumn::make('aliquota_icms')
                    ->label('% ICMS')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    ),
                TextColumn::make('valor_icms')
                    ->label('V.ICMS')
                    ->money('BRL')
                    ->summarize(Sum::make()
                        ->label('Total ICMS')
                        ->money('BRL')),
                TextColumn::make('valor_total')
                    ->label('Valor Total')
                    ->money('BRL')
                    ->summarize(Sum::make()
                        ->label('Total Produto NFe')
                        ->money('BRL')),
            ])
            ->filters([
                // ...
            ])
            ->actions([
                // ...
            ])
            ->bulkActions([
                // ...
            ]);
    }

    public function render()
    {
        return view('livewire.component.product-table-infolist');
    }
}
