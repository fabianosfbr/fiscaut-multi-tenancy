<?php

namespace App\Filament\Contabil\Resources\FornecedorResource\Pages;

use Filament\Actions;
use App\Models\Tenant\PlanoDeConta;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Contabil\Resources\FornecedorResource;

class CreateFornecedor extends CreateRecord
{
    protected static string $resource = FornecedorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        $plano = PlanoDeConta::where('codigo', $data['conta_contabil'])->where('organization_id', getOrganizationCached()->id)->first();

        $data['conta_contabil'] = $plano?->id;

        $data['cnpj'] = sanitize($data['cnpj']);

        $data['organization_id'] = getOrganizationCached()->id;

        return $data;
    }


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
