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


    public function gerarDeclaracaoPdf()
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

        // Adicionar nome do mês em cada registro
        foreach ($dados as $item) {
            $item->mes_nome = $this->getMesNome($item->mes, $item->ano);
        }

        // Calcular total
        $total = array_sum(array_column($dados, 'valor_total'));

        // Formatar período
        $periodoInicial = Carbon::create($startDate->year, $startDate->month)->translatedFormat('F \d\e Y');
        $periodoFinal = Carbon::create($endDate->year, $endDate->month)->translatedFormat('F \d\e Y');

        $pdf = Pdf::loadView('filament.fiscal.pages.declaracao-faturamento-pdf', [
            'organization' => $organization,
            'dados' => $dados,
            'total' => $total,
            'periodoInicial' => ucfirst($periodoInicial),
            'periodoFinal' => ucfirst($periodoFinal),
        ]);

        $name = 'declaracao-faturamento-' . $organization->cnpj . '.pdf';
        
        $this->redirect(request()->header('Referer'));
        // Retorna o PDF sem afetar o estado do componente
        return response()->streamDownload(
            fn () => print($pdf->output()),
            $name
        );
    }



    /**
     * Carrega os dados para o gráfico e tabela
     */
    private function loadData(): void
    {
        $organization = getOrganizationCached();
        $cacheKey = "faturamento_mensal_{$organization->cnpj}";
        

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
     * Retorna o nome do mês em português com a primeira letra maiúscula
     */
    public function getMesNome($mes, $ano): string
    {
        // Obtém o nome do mês em português e converte a primeira letra para maiúscula
        $nomeMes = Carbon::createFromDate($ano, $mes, 1)->translatedFormat('F');
        return ucfirst($nomeMes);
    }
}
