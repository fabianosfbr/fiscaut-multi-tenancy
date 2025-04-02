<?php

namespace App\Filament\Fiscal\Pages;

use Livewire\Livewire;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Models\Tenant\NotaFiscalEletronica;

class EntradaSaidaReport extends Page
{
    protected static ?string $title = 'Relatório de Entrada vs Saída';

    protected static ?string $navigationGroup = 'Relatórios';

    protected static ?int $navigationSort = 13;

    protected static string $view = 'filament.fiscal.pages.entrada-saida-report';

    // Armazena dados do gráfico
    public array $chartData = [];

    // Armazena os dados da tabela
    public array $tableData = [];

    public function mount(): void
    {
        $this->loadData();
    }

    /**
     * Carrega os dados para o gráfico e tabela
     */
    private function loadData(): void
    {
        $organization = getOrganizationCached();
        $endDate = now();
        $startDate = now()->subMonths(11)->startOfMonth();

        // Consultar entradas (notas fiscais de entrada)
        $entradas = DB::select("
            SELECT 
                YEAR(data_emissao) as ano,
                MONTH(data_emissao) as mes,
                COUNT(*) as total_notas,
                SUM(valor_total) as valor_total
            FROM notas_fiscais_eletronica
            WHERE 
                cnpj_destinatario = ? AND 
                status_nota = ? AND 
                data_emissao BETWEEN ? AND ?
            GROUP BY YEAR(data_emissao), MONTH(data_emissao)
            ORDER BY ano DESC, mes DESC
        ", [$organization->cnpj, 'AUTORIZADA', $startDate, $endDate]);

        // Consultar saídas (notas fiscais de saída)
        $saidas = DB::select("
            SELECT 
                YEAR(data_emissao) as ano,
                MONTH(data_emissao) as mes,
                COUNT(*) as total_notas,
                SUM(valor_total) as valor_total
            FROM notas_fiscais_eletronica
            WHERE 
                cnpj_emitente = ? AND 
                status_nota = ? AND 
                data_emissao BETWEEN ? AND ?
            GROUP BY YEAR(data_emissao), MONTH(data_emissao)
            ORDER BY ano DESC, mes DESC
        ", [$organization->cnpj, 'AUTORIZADA', $startDate, $endDate]);

        // Preparar dados para tabela
        $this->prepareTableData($entradas, $saidas);

        // Preparar dados para gráfico
        $this->prepareChartData($entradas, $saidas, $startDate, $endDate);
    }

    /**
     * Prepara os dados para a tabela
     */
    private function prepareTableData(array $entradas, array $saidas): void
    {
        $tableData = [];
        
        $startDate = Carbon::now();

        for ($i = 0; $i < 12; $i++) {
            $tempDate = $startDate->copy()->subMonths($i);
            $ano = $tempDate->year;
            $mes = $tempDate->month;
            
            // Buscar entradas do mês
            $entrada = $this->findMonthData($entradas, $ano, $mes);
            
            // Buscar saídas do mês
            $saida = $this->findMonthData($saidas, $ano, $mes);

            $tableData[] = [
                'ano' => $ano,
                'mes' => $this->getMesNome($mes, $ano),
                'entradas' => $entrada ? $entrada->valor_total : 0,
                'saidas' => $saida ? $saida->valor_total : 0,
                'resultado' => ($entrada ? $entrada->valor_total : 0) - ($saida ? $saida->valor_total : 0),
                'total_notas_entrada' => $entrada ? $entrada->total_notas : 0,
                'total_notas_saida' => $saida ? $saida->total_notas : 0,
            ];           
        }

        $this->tableData = $tableData;
    }

    /**
     * Prepara os dados para o gráfico
     */
    private function prepareChartData(array $entradas, array $saidas, Carbon $startDate, Carbon $endDate): void
    {
        $labels = [];
        $entradasData = [];
        $saidasData = [];
        $resultadosData = [];

        $tempDate = $startDate->copy();
        while ($tempDate->lte($endDate)) {
            $ano = $tempDate->year;
            $mes = $tempDate->month;
            $mesLabel = $tempDate->format('M/Y');

            $labels[] = $mesLabel;

            // Buscar entradas do mês
            $entrada = $this->findMonthData($entradas, $ano, $mes);
            $valorEntrada = $entrada ? $entrada->valor_total : 0;
            $entradasData[] = $valorEntrada;

            // Buscar saídas do mês
            $saida = $this->findMonthData($saidas, $ano, $mes);
            $valorSaida = $saida ? $saida->valor_total : 0;
            $saidasData[] = $valorSaida;

            // Calcular resultado
            $resultadosData[] = $valorEntrada - $valorSaida;

            $tempDate->addMonth();
        }

        $this->chartData = [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Entradas (R$)',
                    'data' => $entradasData,
                    'borderColor' => 'rgba(102, 126, 234, 1)', // Vermelho
                    'backgroundColor' => 'rgba(102, 126, 234, 0.25)',
                    'pointBackgroundColor' => 'rgba(102, 126, 234, 1)',
                    'tension' => 0.3,                  
                    'borderWidth' => 1,
                    'pointHoverRadius' => 5,
                    'pointRadius' => 0,                    
                    'fill' => [
                        'target' => 'origin',
                    ]
                ],
                [
                    'label' => 'Saídas (R$)',
                    'data' => $saidasData,
                    'borderColor' => 'rgba(237, 100, 166, 1)', // Vermelho
                    'backgroundColor' => 'rgba(237, 100, 166, 0.25)',  
                    'tension' => 0.3,                  
                    'borderWidth' => 1,
                    'pointHoverRadius' => 5,
                    'pointRadius' => 0,   
                    'fill' => [
                        'target' => 'origin',
                    ]
                ],

            ]
        ];
    }

    /**
     * Busca dados de um mês específico em um array de dados
     */
    private function findMonthData(array $data, int $ano, int $mes): ?object
    {
        foreach ($data as $item) {
            if ($item->ano == $ano && $item->mes == $mes) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Retorna o nome do mês em português com a primeira letra maiúscula
     */
    public function getMesNome($mes, $ano): string
    {
        $nomeMes = Carbon::createFromDate($ano, $mes, 1)->translatedFormat('F');
        return ucfirst($nomeMes);
    }
} 