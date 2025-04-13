<?php

namespace App\Filament\Contabil\Resources\HistoricoContabilResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Cache;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Contabil\Resources\HistoricoContabilResource;

class EditHistoricoContabil extends EditRecord
{
    protected static string $resource = HistoricoContabilResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function beforeSave(): void
    {
        Cache::forget('historicos_' . getOrganizationCached()->id);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
