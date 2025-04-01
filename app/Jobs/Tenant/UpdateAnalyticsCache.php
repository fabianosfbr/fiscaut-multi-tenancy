<?php

namespace App\Jobs\Tenant;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\AnalyticsCache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Tenant\NotaFiscalEletronica;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Tenant\NotaFiscalEletronicaItem;
use App\Models\Tenant\ConhecimentoTransporteEletronico;

class UpdateAnalyticsCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $organizationId;
    protected $cnpj;

    /**
     * Create a new job instance.
     */
    public function __construct($organizationId, $cnpj)
    {
        $this->organizationId = $organizationId;
        $this->cnpj = $cnpj;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->updateFaturamentoVsCompra();
        $this->updateTopProdutos();
        $this->updateDocsOverview();
        $this->updateTopClientes();
        $this->updateTopFornecedores();
    }

    /**
     * Atualiza dados de Faturamento vs Compra
     */
    protected function updateFaturamentoVsCompra(): void
    {
        $labels = [];
        $faturamentoData = [];
        $compraData = [];
        
        $endDate = now();
        $startDate = now()->subMonths(11);
        
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addMonth()) {
            $monthKey = $date->format('m/Y');
            $labels[] = $monthKey;
            
            // Busca faturamento (notas de saída)
            $faturamento = NotaFiscalEletronica::where('cnpj_emitente', $this->cnpj)
                ->where('status_nota', 'AUTORIZADA')
                ->whereYear('data_emissao', $date->year)
                ->whereMonth('data_emissao', $date->month)
                ->sum('valor_total');
            
            // Busca compras (notas de entrada)
            $compra = NotaFiscalEletronica::where('cnpj_destinatario', $this->cnpj)
                ->where('status_nota', 'AUTORIZADA')
                ->whereYear('data_emissao', $date->year)
                ->whereMonth('data_emissao', $date->month)
                ->sum('valor_total');
            
            $faturamentoData[$monthKey] = $faturamento;
            $compraData[$monthKey] = $compra;
        }
        
        $data = [
            'labels' => $labels,
            'faturamentoData' => $faturamentoData,
            'compraData' => $compraData,
            'updated_at' => now()->toDateTimeString(),
        ];
        
        AnalyticsCache::store("faturamento_vs_compra_{$this->cnpj}", $data);
    }

    /**
     * Atualiza dados de Top Produtos
     */
    protected function updateTopProdutos(): void
    {        
        $startDate = Carbon::now();        
        // Para cada mês dos últimos 12 meses
        for ($i = 0; $i < 12; $i++) {
            $date = $startDate->copy()->subMonths($i); 
            $monthKey = $date->format('m/Y');
            $dadosProdutos = NotaFiscalEletronicaItem::select(
                'codigo',
                'descricao',
                DB::raw('SUM(quantidade) as total_quantidade'),
                DB::raw('SUM(valor_total) as total_valor')
            )
            ->whereHas('notaFiscal', function ($query) use ($date) {
                $query->where('cnpj_emitente', $this->cnpj)
                    ->where('status_nota', 'AUTORIZADA')
                    ->whereYear('data_emissao', $date->year)
                    ->whereMonth('data_emissao', $date->month);
            })
            ->groupBy('codigo', 'descricao')
            ->orderBy('total_quantidade', 'desc')
            ->limit(15)
            ->get()
            ->toArray();
            
            AnalyticsCache::store("top_produtos_{$this->cnpj}_{$monthKey}", [
                'data' => $dadosProdutos,
                'updated_at' => now()->toDateTimeString(),
            ]);
        }
    }

    /**
     * Atualiza dados do DocsOverview
     */
    protected function updateDocsOverview(): void
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // NF-es Emitidas
        $nfeEmitidas = NotaFiscalEletronica::where('cnpj_emitente', $this->cnpj)
            ->whereMonth('data_emissao', $currentMonth)
            ->whereYear('data_emissao', $currentYear)
            ->count();
            
        // NF-es Recebidas
        $nfeRecebidas = NotaFiscalEletronica::where('cnpj_destinatario', $this->cnpj)
            ->whereMonth('data_emissao', $currentMonth)
            ->whereYear('data_emissao', $currentYear)
            ->count();
            
        // CT-es Emitidos
        $cteEmitidos = ConhecimentoTransporteEletronico::where('cnpj_emitente', $this->cnpj)
            ->whereMonth('data_emissao', $currentMonth)
            ->whereYear('data_emissao', $currentYear)
            ->count();
            
        // CT-es Recebidos
        $cteTomados = ConhecimentoTransporteEletronico::where('cnpj_tomador', $this->cnpj)
            ->whereMonth('data_emissao', $currentMonth)
            ->whereYear('data_emissao', $currentYear)
            ->count();
            
        $data = [
            'nfe_emitidas' => $nfeEmitidas,
            'nfe_recebidas' => $nfeRecebidas,
            'cte_emitidos' => $cteEmitidos,
            'cte_tomados' => $cteTomados,
            'updated_at' => now()->toDateTimeString(),
        ];
        
        AnalyticsCache::store("docs_overview_{$this->cnpj}_{$currentMonth}_{$currentYear}", $data);
    }

    /**
     * Atualiza dados de Top Clientes
     */
    protected function updateTopClientes(): void
    {        
        $startDate = Carbon::now();        
        // Para cada mês dos últimos 12 meses
        for ($i = 0; $i < 12; $i++) {
            $date = $startDate->copy()->subMonths($i); 
            $monthKey = $date->format('m/Y');
            
            $dadosClientes = NotaFiscalEletronica::select(
                'cnpj_destinatario',
                'nome_destinatario as nome_cliente',
                DB::raw('SUM(valor_total) as total_valor'),
                DB::raw('COUNT(*) as quantidade_notas')
            )
            ->where('cnpj_emitente', $this->cnpj)
            ->where('status_nota', 'AUTORIZADA')
            ->whereYear('data_emissao', $date->year)
            ->whereMonth('data_emissao', $date->month)
            ->groupBy('cnpj_destinatario', 'nome_destinatario')
            ->orderBy('total_valor', 'desc')
            ->limit(15)
            ->get()
            ->toArray();
            
            AnalyticsCache::store("top_clientes_{$this->cnpj}_{$monthKey}", [
                'data' => $dadosClientes,
                'updated_at' => now()->toDateTimeString(),
            ]);
        }
    }

    /**
     * Atualiza dados de Top Fornecedores
     */
    protected function updateTopFornecedores(): void
    {        
        $startDate = Carbon::now();        
        // Para cada mês dos últimos 12 meses
        for ($i = 0; $i < 12; $i++) {
            $date = $startDate->copy()->subMonths($i); 
            $monthKey = $date->format('m/Y');
            
            $dadosFornecedores = NotaFiscalEletronica::select(
                'cnpj_emitente',
                'nome_emitente as nome_fornecedor',
                DB::raw('SUM(valor_total) as total_valor'),
                DB::raw('COUNT(*) as quantidade_notas')
            )
            ->where('cnpj_destinatario', $this->cnpj)
            ->where('status_nota', 'AUTORIZADA')
            ->whereYear('data_emissao', $date->year)
            ->whereMonth('data_emissao', $date->month)
            ->groupBy('cnpj_emitente', 'nome_emitente')
            ->orderBy('total_valor', 'desc')
            ->limit(15)
            ->get()
            ->toArray();
            
            AnalyticsCache::store("top_fornecedores_{$this->cnpj}_{$monthKey}", [
                'data' => $dadosFornecedores,
                'updated_at' => now()->toDateTimeString(),
            ]);
        }
    }
} 