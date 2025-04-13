<?php

namespace App\Filament\Contabil\Resources\HistoricoContabilResource\Pages;

use App\Filament\Contabil\Resources\HistoricoContabilResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateHistoricoContabil extends CreateRecord
{
    protected static string $resource = HistoricoContabilResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_id'] = getOrganizationCached()->id;

        return $data;
    }

    // afterCreate
    protected function afterCreate(): void
    {
        Cache::forget('historicos_' . getOrganizationCached()->id);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
