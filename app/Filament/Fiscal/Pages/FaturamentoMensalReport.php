<?php

namespace App\Filament\Fiscal\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\NotaFiscalEletronica;

class FaturamentoMensalReport extends Page
{
    protected static ?string $title = 'Relatório de Faturamento Mensal';

    protected static ?string $navigationGroup = 'Relatórios';


    protected static ?int $navigationSort = 12;

    protected static string $view = 'filament.fiscal.pages.faturamento-mensal-report';

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

        // Consultar faturamento por mês
        $dados = DB::select("
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
        $this->tableData = $dados;
        
        // Preparar dados para gráfico
        $faturamentoData = [];
        $labels = [];
        
        // Ordenando do mais antigo para o mais recente para o gráfico
        usort($dados, function ($a, $b) {
            if ($a->ano != $b->ano) {
                return $a->ano - $b->ano;
            }
            return $a->mes - $b->mes;
        });
        
        // Preparar array com todos os meses, mesmo os sem faturamento
        $tempDate = $startDate->copy();
        while ($tempDate->lte($endDate)) {
            $ano = $tempDate->year;
            $mes = $tempDate->month;
            $mesLabel = $tempDate->format('M/Y');
            
            $labels[] = $mesLabel;
            
            // Procurar se existe faturamento para este mês
            $valor = 0;
            foreach ($dados as $dado) {
                if ($dado->ano == $ano && $dado->mes == $mes) {
                    $valor = $dado->valor_total;
                    break;
                }
            }
            
            $faturamentoData[] = $valor;
            $tempDate->addMonth();
        }

        $this->chartData = [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Faturamento (R$)',
                    'data' => $faturamentoData,
                    'backgroundColor' => '#4F46E5',
                    'borderColor' => '#4338CA',
                    'borderWidth' => 1
                ]
            ]
        ];
    }
    
    /**
     * Formata valor para exibição em moeda brasileira
     */
    public function formatMoney($value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
    
    /**
     * Retorna o nome do mês em português com a primeira letra maiúscula
     */
    public function getMesNome($mes, $ano): string
    {
        // Obtém o nome do mês em português e converte a primeira letra para maiúscula
        $nomeMes = Carbon::createFromDate($ano, $mes, 1)->translatedFormat('F');
        return ucfirst($nomeMes);
    }
}
