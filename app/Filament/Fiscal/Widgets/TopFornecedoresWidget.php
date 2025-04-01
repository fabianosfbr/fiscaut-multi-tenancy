<?php

namespace App\Filament\Fiscal\Widgets;

use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\AnalyticsCache;
use App\Jobs\Tenant\UpdateAnalyticsCache;
use App\Models\Tenant\NotaFiscalEletronica;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class TopFornecedoresWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Principais Fornecedores';

    protected static ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 6;

    protected static ?int $sort = 4;

    public ?string $filter = 'first_day';

    // Filtro de período
    protected function getFilters(): ?array
    {
        $options = [];
        $currentDate = now();
        // Gerar opções para os últimos 12 meses
        for ($i = 0; $i < 12; $i++) {
            $date = $currentDate->copy()->subMonths($i);
            $key = $date->format('m/Y');
            $options[$date->format('m/Y')] = $date->format('m/Y');
        }

        return $options;
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        if ($activeFilter == 'first_day') $activeFilter = now()->format('m/Y');

        $organization = getOrganizationCached();

        // Buscar do cache do banco de dados
        $cachedData = AnalyticsCache::retrieve("top_fornecedores_{$organization->cnpj}_{$activeFilter}", 7200);

        if (!$cachedData) {
            // Se não encontrado, programar atualização do cache
            UpdateAnalyticsCache::dispatch($organization->id, $organization->cnpj);

            // Enquanto isso, buscar dados diretamente do banco
            return $this->getDataFromDatabase($organization, $activeFilter);
        }

        // Preparar dados do cache para o gráfico
        $dadosFornecedores = $cachedData['data'] ?? [];

        // Caso não haja dados no cache
        if (empty($dadosFornecedores)) {
            return $this->getDataFromDatabase($organization, $activeFilter);
        }

        // Preparar dados para o gráfico
        $labels = collect($dadosFornecedores)->pluck('nome_fornecedor')
            ->map(function ($nome) {
                return strlen($nome) > 20 ? substr($nome, 0, 17) . '...' : $nome;
            })
            ->toArray();

        $dadosValores = collect($dadosFornecedores)->pluck('total_valor')->toArray();

        // Cores vibrantes alternadas
        $cores = [
            '#42A5F5',
            '#FFA726',
            '#EC407A',
            '#66BB6A',
            '#9CCC65',
            '#26C6DA',
            '#F06292',
            '#8BC34A',
            '#29B6F6',
            '#FF9800',
            '#5C6BC0',
            '#FFCA28',
            '#26A69A',
            '#AB47BC',
            '#EF5350'
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Valor Total (R$)',
                    'data' => $dadosValores,
                    'backgroundColor' => $cores,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Método fallback para obter dados diretamente do banco
     */
    protected function getDataFromDatabase($organization, $period): array
    {
        // Converter m/Y para objeto de data
        $partes = explode('/', $period);
        $mes = $partes[0] ?? now()->month;
        $ano = $partes[1] ?? now()->year;

        $dataPesquisa = \Carbon\Carbon::createFromDate($ano, $mes, 1);

        $dadosFornecedores = NotaFiscalEletronica::select(
            'cnpj_emitente',
            'nome_emitente as nome_fornecedor',
            DB::raw('SUM(valor_total) as total_valor'),
            DB::raw('COUNT(*) as quantidade_notas')
        )
            ->where('cnpj_destinatario', $organization->cnpj)
            ->where('status_nota', 'AUTORIZADA')
            ->whereYear('data_emissao', $dataPesquisa->year)
            ->whereMonth('data_emissao', $dataPesquisa->month)
            ->groupBy('cnpj_emitente', 'nome_emitente')
            ->orderBy('total_valor', 'desc')
            ->limit(15)
            ->get();

        // Preparar dados para o gráfico
        $labels = $dadosFornecedores->pluck('nome_fornecedor')
            ->map(function ($nome) {
                return strlen($nome) > 20 ? substr($nome, 0, 17) . '...' : $nome;
            })
            ->toArray();

        $dadosValores = $dadosFornecedores->pluck('total_valor')->toArray();

        // Cores vibrantes alternadas
        $cores = [
            '#42A5F5',
            '#FFA726',
            '#EC407A',
            '#66BB6A',
            '#9CCC65',
            '#26C6DA',
            '#F06292',
            '#8BC34A',
            '#29B6F6',
            '#FF9800',
            '#5C6BC0',
            '#FFCA28',
            '#26A69A',
            '#AB47BC',
            '#EF5350'
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Valor Total (R$)',
                    'data' => $dadosValores,
                    'backgroundColor' => $cores,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
        {
            maintainAspectRatio: false,
            scales: {
                y: {
                    ticks: {
                       display: false,
                    },
                },
                x: {
                    ticks: {
                        display: false,
                    },
                },
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'right',
                },
                tooltip: {
                    callbacks:{
                        label: function(context) {
                            return context.label + ': R$ ' + context.parsed.toLocaleString('pt-BR', {
                                style: 'currency',
                                currency: 'BRL'
                            });
                        }
                    }
                },
            },
        }
    JS);
    }
}
