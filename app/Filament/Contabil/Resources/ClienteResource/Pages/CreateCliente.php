<?php

namespace App\Filament\Contabil\Resources\ClienteResource\Pages;

use Filament\Actions;
use App\Models\Tenant\PlanoDeConta;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Contabil\Resources\ClienteResource;

class CreateCliente extends CreateRecord
{
    protected static string $resource = ClienteResource::class;

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
