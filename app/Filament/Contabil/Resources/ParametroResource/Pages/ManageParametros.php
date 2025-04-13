<?php

namespace App\Filament\Contabil\Resources\ParametroResource\Pages;

use Filament\Actions;
use App\Models\Tenant\PlanoDeConta;
use App\Models\Tenant\HistoricoContabil;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Contabil\Resources\ParametroResource;

class ManageParametros extends ManageRecords
{
    protected static string $resource = ParametroResource::class;

    protected static ?string $title = 'Parâmetros';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Criar Parâmetro')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['organization_id'] = getOrganizationCached()->id;

                    $conta_contabil = PlanoDeConta::where('codigo', $data['conta_contabil'])->where('organization_id', getOrganizationCached()->id)->first();

                    $historico = HistoricoContabil::where('codigo', $data['codigo_historico'])->where('organization_id', getOrganizationCached()->id)->first();

                    $data['conta_contabil'] = $conta_contabil->id;

                    $descricao_conta_contabil = [
                        'codigo' => $conta_contabil->codigo,
                        'descricao' => $conta_contabil->nome,
                    ];

                    $descricao_historico = [
                        'id' => $historico->id,
                        'descricao' => $historico->descricao,
                    ];

                    $data['descricao_conta_contabil'] = $descricao_conta_contabil;
                    $data['descricao_historico'] = $descricao_historico;
                    $data['codigo'] = $data['params'];
                    $data['descricao'] = $data['params'];

                    return $data;
                }),
        ];
    }
}
