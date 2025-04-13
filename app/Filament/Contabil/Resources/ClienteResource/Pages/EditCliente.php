<?php

namespace App\Filament\Contabil\Resources\ClienteResource\Pages;

use Filament\Actions;
use App\Models\Tenant\PlanoDeConta;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Contabil\Resources\ClienteResource;

class EditCliente extends EditRecord
{
    protected static string $resource = ClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {

        $plano = PlanoDeConta::find($data['conta_contabil']);

        $data['conta_contabil'] = $plano?->codigo;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
