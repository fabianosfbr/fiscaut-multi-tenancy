<?php

namespace App\Filament\Fiscal\Widgets;

use App\Models\Tenant\NotaFiscalEletronica;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Tenant\ConhecimentoTransporteEletronico;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Cache;

class DocsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $organization = getOrganizationCached();
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $nfeEmitidas = Cache::remember("nfe_emitidas_{$organization->cnpj}_{$currentMonth}_{$currentYear}", 7200, function () use ($organization, $currentMonth, $currentYear) {
            return NotaFiscalEletronica::where('cnpj_emitente', $organization->cnpj)
                ->whereMonth('data_emissao', $currentMonth)
                ->whereYear('data_emissao', $currentYear)
                ->count();
        });

        $nfeRecebidas = Cache::remember("nfe_recebidas_{$organization->cnpj}_{$currentMonth}_{$currentYear}", 7200, function () use ($organization, $currentMonth, $currentYear) {
            return NotaFiscalEletronica::where('cnpj_destinatario', $organization->cnpj)
                ->whereMonth('data_emissao', $currentMonth)
                ->whereYear('data_emissao', $currentYear)
                ->count();
        });

        $cteEmitidos = Cache::remember("cte_emitidos_{$organization->cnpj}_{$currentMonth}_{$currentYear}", 7200, function () use ($organization, $currentMonth, $currentYear) {
            return ConhecimentoTransporteEletronico::where('cnpj_emitente', $organization->cnpj)
                ->whereMonth('data_emissao', $currentMonth)
                ->whereYear('data_emissao', $currentYear)
                ->count();
        });

        $cteTomados = Cache::remember("cte_tomados_{$organization->cnpj}_{$currentMonth}_{$currentYear}", 7200, function () use ($organization, $currentMonth, $currentYear) {
            return ConhecimentoTransporteEletronico::where('cnpj_tomador', $organization->cnpj)
                ->whereMonth('data_emissao', $currentMonth)
                ->whereYear('data_emissao', $currentYear)
                ->count();
        });

        return [
            Stat::make('NF-es Emitidas no Mês', $nfeEmitidas),
            Stat::make('NF-es Recebidas no Mês', $nfeRecebidas),
            Stat::make('CT-es Emitidos no Mês', $cteEmitidos),
            Stat::make('CT-es Recebidos no Mês', $cteTomados),
        ];
    }
}
