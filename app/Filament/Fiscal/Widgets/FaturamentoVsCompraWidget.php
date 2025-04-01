<?php

namespace App\Filament\Fiscal\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Tenant\NotaFiscalEletronica;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class FaturamentoVsCompraWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Faturamento vs Compra';

    protected static ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 6;

    protected static ?int $sort = 1;

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
        return 0; // 2 horas
    }

    protected function getCacheKey(): string
    {
        $organization = getOrganizationCached();
        return "faturamento_vs_compra_widget_{$organization->cnpj}";
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

        $labels = [];
        $faturamentoData = [];
        $compraData = [];

        $endDate = now();
        $startDate = now()->subMonths(11);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addMonth()) {
            // Formato para exibição no gráfico (MM-YYYY)
            $labels[] = $date->format('m-Y');

            // Busca faturamento (notas de saída)
            $faturamento = NotaFiscalEletronica::where('cnpj_emitente', $organization->cnpj)
                ->where('status_nota', 'AUTORIZADA')
                ->where(function ($query) use ($dataPesquisa) {
                    $query->whereYear('data_emissao', $dataPesquisa->year)
                        ->whereMonth('data_emissao', $dataPesquisa->month);
                })
                ->sum('valor_total');

            // Busca compras (notas de entrada)
            $compra = NotaFiscalEletronica::where('cnpj_destinatario', $organization->cnpj)
                ->where('status_nota', 'AUTORIZADA')
                ->where(function ($query) use ($dataPesquisa) {
                    $query->whereYear('data_emissao', $dataPesquisa->year)
                        ->whereMonth('data_emissao', $dataPesquisa->month);
                })
                ->sum('valor_total');

            $faturamentoData[] = $faturamento;
            $compraData[] = $compra;
        }

        // Formata labels para exibição (MM-YYYY)
        $formattedLabels = [];
        foreach ($labels as $label) {
            $parts = explode('-', $label);
            $formattedLabels[] = $parts[0] . '/' . $parts[1];
        }

        $data = [
            'labels' => $formattedLabels,
            'faturamentoData' => $faturamentoData,
            'compraData' => $compraData,
        ];;



        return [
            'datasets' => [
                [
                    'label' => 'Faturamento',
                    'backgroundColor' => 'rgba(102, 126, 234, 0.5)',
                    'borderWidth' => 1,
                    'tension' => 0.1,
                    'data' => $data['faturamentoData'],
                    'tooltip' => [
                        'callbacks' => [],
                    ],
                    'fill' => [
                        'target' => 'origin',
                    ],
                ],
                [
                    'label' => 'Compra',
                    'backgroundColor' => 'rgba(237, 100, 166, 0.5)',
                    'data' => $data['compraData'],
                    'borderWidth' => 1,
                    'tension' => 0.1,
                    'fill' => [
                        'target' => 'origin',
                    ],
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
