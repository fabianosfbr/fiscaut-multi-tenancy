<?php

namespace App\Filament\Contabil\Resources\ImportarLancamentoContabilResource\Widgets;

use App\Models\Tenant\ImportarLancamentoContabil;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class ImportLancamentoOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $resultados = ImportarLancamentoContabil::where('organization_id', getOrganizationCached()->id)
            ->where('user_id', Auth::user()->id)
            ->selectRaw('COUNT(*) as total_registros')
            ->selectRaw('SUM(CASE WHEN is_exist = 1 THEN 1 ELSE 0 END) as total_vinculados')
            ->selectRaw('SUM(CASE WHEN is_exist = 0 THEN 1 ELSE 0 END) as total_desvinculados')
            ->first();

        return [
            Stat::make('Nº registros importados', $resultados->total_registros),
            Stat::make('Nº registros com vínculo', $resultados->total_vinculados ?? 0),
            Stat::make('Nº registros sem vínculo', $resultados->total_desvinculados ?? 0),
        ];
    }
}
