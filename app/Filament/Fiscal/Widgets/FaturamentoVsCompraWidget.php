<?php

namespace App\Filament\Fiscal\Widgets;

use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use App\Models\Tenant\AnalyticsCache;
use App\Jobs\Tenant\UpdateAnalyticsCache;
use App\Models\Tenant\NotaFiscalEletronica;

class FaturamentoVsCompraWidget extends ChartWidget
{

    protected static ?string $heading = 'Faturamento vs Compra';

    protected static ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 6;

    protected static ?int $sort = 1;



    protected function getData(): array
    {
        $activeFilter = $this->filter;
        if ($activeFilter == 'first_day') $activeFilter = now()->format('m/Y');

        $organization = getOrganizationCached();

        // Obter dados do cache - expiração de 2 horas
        $cachedData = AnalyticsCache::retrieve("faturamento_vs_compra_{$organization->cnpj}", 7200);
        // Se não tiver em cache, buscar e armazenar para próximos acessos
        if (!$cachedData) {
            // Disparar job em segundo plano para atualizar o cache
            UpdateAnalyticsCache::dispatch($organization->id, $organization->cnpj);

            // Enquanto isso, fazer a consulta normalmente para não bloquear a interface
            return $this->getDataFromDatabase($organization);
        }

        // Filtrar os dados conforme o mês selecionado
        $labels = $cachedData['labels'] ?? [];
        $faturamentoData = $cachedData['faturamentoData'] ?? [];
        $compraData = $cachedData['compraData'] ?? [];

        $faturamentoDataArray = [];
        $compraDataArray = [];

        foreach ($labels as $label) {
            $faturamentoDataArray[] = $faturamentoData[$label] ?? 0;
            $compraDataArray[] = $compraData[$label] ?? 0;
        }

        $faturamentoData = $faturamentoDataArray;
        $compraData = $compraDataArray;

        return [
            'datasets' => [
                [
                    'label' => 'Faturamento',
                    'backgroundColor' => 'rgba(102, 126, 234, 0.5)',
                    'borderWidth' => 1,
                    'tension' => 0.1,
                    'data' => $faturamentoData,
                    'fill' => [
                        'target' => 'origin',
                    ],
                ],
                [
                    'label' => 'Compra',
                    'backgroundColor' => 'rgba(237, 100, 166, 0.5)',
                    'data' => $compraData,
                    'borderWidth' => 1,
                    'tension' => 0.1,
                    'fill' => [
                        'target' => 'origin',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Método de fallback para obter dados diretamente do banco quando cache não disponível
     */
    protected function getDataFromDatabase($organization): array
    {
        $labels = [];
        $faturamentoData = [];
        $compraData = [];

        $endDate = now();
        $startDate = now()->subMonths(11);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addMonth()) {
            // Formato para exibição no gráfico
            $labels[] = $date->format('m/Y');

            // Busca faturamento (notas de saída)
            $faturamento = NotaFiscalEletronica::where('cnpj_emitente', $organization->cnpj)
                ->where('status_nota', 'AUTORIZADA')
                ->whereYear('data_emissao', $date->year)
                ->whereMonth('data_emissao', $date->month)
                ->sum('valor_total');

            // Busca compras (notas de entrada)
            $compra = NotaFiscalEletronica::where('cnpj_destinatario', $organization->cnpj)
                ->where('status_nota', 'AUTORIZADA')
                ->whereYear('data_emissao', $date->year)
                ->whereMonth('data_emissao', $date->month)
                ->sum('valor_total');

            $faturamentoData[] = $faturamento;
            $compraData[] = $compra;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Faturamento',
                    'backgroundColor' => 'rgba(102, 126, 234, 0.5)',
                    'borderWidth' => 1,
                    'tension' => 0.1,
                    'data' => $faturamentoData,
                    'fill' => [
                        'target' => 'origin',
                    ],
                ],
                [
                    'label' => 'Compra',
                    'backgroundColor' => 'rgba(237, 100, 166, 0.5)',
                    'data' => $compraData,
                    'borderWidth' => 1,
                    'tension' => 0.1,
                    'fill' => [
                        'target' => 'origin',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
        {
            scales: {
                y: {
                    ticks: {
                        callback: (value) => value.toLocaleString('pt-BR', {
                            style: 'currency',
                            currency: 'BRL'
                        }),
                    },
                },
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': R$ ' + context.raw.toLocaleString('pt-BR', {
                                style: 'currency',
                                currency: 'BRL'
                            });
                        }
                    }
                }
            },
        }
    JS);
    }
}
