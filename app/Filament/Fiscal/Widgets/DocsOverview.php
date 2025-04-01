<?php

namespace App\Filament\Fiscal\Widgets;

use App\Models\Tenant\NotaFiscalEletronica;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Tenant\ConhecimentoTransporteEletronico;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use App\Models\Tenant\AnalyticsCache;
use App\Jobs\Tenant\UpdateAnalyticsCache;

class DocsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $organization = getOrganizationCached();
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Buscar dados do cache do banco de dados
        $cachedData = AnalyticsCache::retrieve("docs_overview_{$organization->cnpj}_{$currentMonth}_{$currentYear}", 7200);
        
        if (!$cachedData) {
            // Se não encontrado, programar atualização do cache
            UpdateAnalyticsCache::dispatch($organization->id, $organization->cnpj);
            
            // Enquanto isso, buscar dados diretamente do banco
            return $this->getDataFromDatabase($organization, $currentMonth, $currentYear);
        }
        
        return [
            Stat::make('NFes emitidas no mês', $cachedData['nfe_emitidas']),
            Stat::make('NFes recebidas no mês', $cachedData['nfe_recebidas']),
            Stat::make('CTes emitidos no mês', $cachedData['cte_emitidos']),
            Stat::make('CTes recebidos no mês', $cachedData['cte_tomados']),
        ];
    }
    
    /**
     * Método de fallback para obter dados diretamente do banco quando cache não disponível
     */
    protected function getDataFromDatabase($organization, $currentMonth, $currentYear): array
    {
        $nfeEmitidas = NotaFiscalEletronica::where('cnpj_emitente', $organization->cnpj)
            ->whereMonth('data_emissao', $currentMonth)
            ->whereYear('data_emissao', $currentYear)
            ->count();

        $nfeRecebidas = NotaFiscalEletronica::where('cnpj_destinatario', $organization->cnpj)
            ->whereMonth('data_emissao', $currentMonth)
            ->whereYear('data_emissao', $currentYear)
            ->count();

        $cteEmitidos = ConhecimentoTransporteEletronico::where('cnpj_emitente', $organization->cnpj)
            ->whereMonth('data_emissao', $currentMonth)
            ->whereYear('data_emissao', $currentYear)
            ->count();

        $cteTomados = ConhecimentoTransporteEletronico::where('cnpj_tomador', $organization->cnpj)
            ->whereMonth('data_emissao', $currentMonth)
            ->whereYear('data_emissao', $currentYear)
            ->count();

        return [
            Stat::make('NF-es emitidas no mês', $nfeEmitidas),
            Stat::make('NF-es recebidas no mês', $nfeRecebidas),
            Stat::make('CT-es emitidos no mês', $cteEmitidos),
            Stat::make('CT-es recebidos no mês', $cteTomados),
        ];
    }
}
