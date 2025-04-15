<?php

namespace App\Filament\Resources\CfopResource\Pages;

use App\Filament\Resources\CfopResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Actions\Action;

class ListCfops extends ListRecords
{
    protected static string $resource = CfopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \EightyNine\ExcelImport\ExcelImportAction::make()
                ->label('Importar CFOPs')
                ->modalDescription('Importe CFOPs a partir de um arquivo Excel.')              
                ->validateUsing([
                    'codigo' => 'required',
                    'descricao' => 'required',
                ])
                ->color("primary"),

                
            Actions\CreateAction::make()
                ->label('Adicionar CFOP')
                ->color("primary"),
        ];
    }
}
