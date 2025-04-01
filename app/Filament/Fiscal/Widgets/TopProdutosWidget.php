<?php

namespace App\Filament\Fiscal\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Components\DatePicker;
use App\Models\Tenant\NotaFiscalEletronicaItem;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class TopProdutosWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Produtos mais vendidos';

    protected static ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 6;

    protected static ?int $sort = 2;

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

    protected function getCacheLifetime(): ?int
    {
        return 7200;
    }

    protected function getCacheKey(): string
    {
        $organization = getOrganizationCached();
        $dataInicial = $this->dataInicial ?? now()->format('m/Y');
        return "top_produtos_widget_{$organization->cnpj}_{$dataInicial}";
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        if ($activeFilter == 'first_day')  $activeFilter = now()->format('m/Y');

        $organization = getOrganizationCached();

        // Converter m/Y para objeto de data
        $partes = explode('/', $activeFilter);
        $mes = $partes[0] ?? now()->month;
        $ano = $partes[1] ?? now()->year;

        $dataPesquisa = \Carbon\Carbon::createFromDate($ano, $mes, 1);


        $dadosProdutos = NotaFiscalEletronicaItem::select(
            'codigo',
            'descricao',
            DB::raw('SUM(quantidade) as total_quantidade'),
            DB::raw('SUM(valor_total) as total_valor')
        )
            ->whereHas('notaFiscal', function ($query) use ($organization, $dataPesquisa) {
                $query->where('cnpj_emitente', $organization->cnpj)
                    ->where('status_nota', 'AUTORIZADA')
                    ->where(function ($query) use ($dataPesquisa) {
                        $query->whereYear('data_emissao', $dataPesquisa->year)
                            ->whereMonth('data_emissao', $dataPesquisa->month);
                    });
            })
            ->groupBy('codigo', 'descricao')
            ->orderBy('total_quantidade', 'desc')
            ->limit(15)
            ->get();

       


        // Preparar dados para o gráfico
        $labels = $dadosProdutos->pluck('descricao')
            ->map(function ($descricao) {
                return strlen($descricao) > 20 ? substr($descricao, 0, 17) . '...' : $descricao;
            })
            ->toArray();

        $dadosQuantidade = $dadosProdutos->pluck('total_quantidade')->toArray();

        // Cores vibrantes alternadas como na imagem
        $cores = [
            '#8BC34A',
            '#42A5F5',
            '#EC407A',
            '#FF9800',
            '#9CCC65',
            '#29B6F6',
            '#F06292',
            '#FFA726',
            '#66BB6A',
            '#26C6DA',
            '#AB47BC',
            '#FFCA28',
            '#26A69A',
            '#5C6BC0',
            '#EF5350'
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Quantidade Vendida',
                    'data' => $dadosQuantidade,
                    'backgroundColor' => $cores,
                    'borderWidth' => 0, // Sem borda para ficar como na imagem
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): ?array
    {
        return [
            'animation' => [
                'delay' => 1000,
            ],
            'scales' => [
                'x' => [
                    'ticks' => [
                        'display' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                    'labels' => [
                        // 'color' => 'rgb(255, 99, 132)'
                    ],
                ],


            ],
        ];
    }
}
