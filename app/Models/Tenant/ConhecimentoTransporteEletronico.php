<?php

namespace App\Models\Tenant;

use App\Enums\Tenant\OrigemCteEnum;
use App\Enums\Tenant\StatusCteEnum;
use Illuminate\Database\Eloquent\Model;
use App\Enums\Tenant\StatusManifestoCteEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Tenant\Concerns\HasTags;
use App\Models\Tenant\Concerns\HasEscrituracao;
use App\Models\Tenant\Concerns\HasDocumentoReferencias;
use App\Interfaces\DocumentoFiscal;

class ConhecimentoTransporteEletronico extends Model implements DocumentoFiscal
{
    use HasTags, HasUuids, HasEscrituracao, HasDocumentoReferencias;

    protected $guarded = ['id'];

    protected $table = 'conhecimentos_transportes_eletronico';

    protected $casts = [
        'data_emissao' => 'datetime',
        'data_entrada' => 'datetime',
        'valor_total' => 'decimal:2',
        'valor_receber' => 'decimal:2',
        'valor_servico' => 'decimal:2',
        'valor_icms' => 'decimal:2',
        'base_calculo_icms' => 'decimal:2',
        'aliquota_icms' => 'decimal:2',
        'peso_bruto' => 'decimal:3',
        'peso_base_calculo' => 'decimal:3',
        'peso_aferido' => 'decimal:3',
        'status_cte' => StatusCteEnum::class,
        'status_manifestacao' => StatusManifestoCteEnum::class,
        'origem' => OrigemCteEnum::class,
    ];

    public function historicos()
    {
        return $this->hasMany(ConhecimentoTransporteEletronicoHistorico::class, 'cte_id');
    }

    /**
     * Escopo para filtrar CTEs de entrada onde a empresa logada é o destinatário
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Organization|null $organization Organização logada
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEntradasDestinatario($query, $organization = null)
    {
        $organization = $organization ?? getOrganizationCached();
        
        return $query->where('cnpj_destinatario', $organization->cnpj);
    }
    
    /**
     * Escopo para filtrar CTEs onde a empresa logada é o emitente
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Organization|null $organization Organização logada
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEmitidos($query, $organization = null)
    {
        $organization = $organization ?? getOrganizationCached();
        
        return $query->where('cnpj_emitente', $organization->cnpj);
    }
    
    /**
     * Escopo para filtrar CTEs onde a empresa logada é o transportador
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Organization|null $organization Organização logada
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeComoTransportador($query, $organization = null)
    {
        $organization = $organization ?? getOrganizationCached();
        
        return $query->where('cnpj_transportador', $organization->cnpj)
                    ->where('cnpj_emitente', '<>', $organization->cnpj);
    }
    
    /**
     * Verifica se o CTe possui DIFAL
     * 
     * @return bool
     */
    public function possuiDifal(): bool
    {
        // Verifica se é uma operação interestadual
        if ($this->uf_emitente === $this->uf_destinatario) {
            return false;
        }
        
        // Verifica se há base de cálculo de ICMS
        if (empty($this->base_calculo_icms) || $this->base_calculo_icms <= 0) {
            return false;
        }
        
        // Se a alíquota de destino for maior que a de origem, pode ter DIFAL
        $aliquotaDestino = $this->obterAliquotaDestino();
        $aliquotaOrigem = $this->aliquota_icms ?? 0;
        
        return ($aliquotaDestino > $aliquotaOrigem) && $this->calcularDifal() > 0;
    }
    
    /**
     * Escopo para filtrar CTEs com DIFAL
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeComDifal($query)
    {
        return $query->whereColumn('uf_emitente', '!=', 'uf_destinatario')
                    ->whereNotNull('base_calculo_icms')
                    ->where('base_calculo_icms', '>', 0);
    }
    
    /**
     * Calcula o valor do DIFAL para o CTe
     * 
     * @return float
     */
    public function calcularDifal(): float
    {
        // Se não tiver base de cálculo, retorna zero
        if (empty($this->base_calculo_icms) || $this->base_calculo_icms <= 0) {
            return 0;
        }
        
        // Obtém a alíquota de destino (interna do estado de destino)
        $aliquotaDestino = $this->obterAliquotaDestino();
        
        // Usa a alíquota informada no CTe como alíquota de origem
        $aliquotaOrigem = $this->aliquota_icms ?? 0;
        
        // Se alíquota destino for menor ou igual à origem, não tem DIFAL
        if ($aliquotaDestino <= $aliquotaOrigem) {
            return 0;
        }
        
        // Cálculo do DIFAL: Base de Cálculo * (Alíquota Destino - Alíquota Origem)
        return $this->base_calculo_icms * (($aliquotaDestino - $aliquotaOrigem) / 100);
    }
    
    /**
     * Obtém a alíquota de destino (interna do estado destinatário)
     * 
     * @return float
     */
    private function obterAliquotaDestino(): float
    {
        // Verifica se a UF de destino é válida
        if (empty($this->uf_destinatario)) {
            return 18.0; // Valor padrão
        }
        
        // Busca a alíquota interna diretamente na tabela de alíquotas
        $aliquotasInterestaduais = config('aliquotas_icms.valor_icms');
        
        if (isset($aliquotasInterestaduais[$this->uf_destinatario]) && isset($aliquotasInterestaduais['UF'])) {
            $indexUfDestino = array_search($this->uf_destinatario, $aliquotasInterestaduais['UF']);
            
            if ($indexUfDestino !== false && isset($aliquotasInterestaduais[$this->uf_destinatario][$indexUfDestino])) {
                return (float) $aliquotasInterestaduais[$this->uf_destinatario][$indexUfDestino];
            }
        }
        
        // Se não encontrar, retorna um valor padrão baseado na UF de destino
        $aliquotasPadrao = [
            'AC' => 17, 'AL' => 17, 'AM' => 18, 'AP' => 18, 'BA' => 18,
            'CE' => 18, 'DF' => 18, 'ES' => 17, 'GO' => 17, 'MA' => 18,
            'MG' => 18, 'MS' => 17, 'MT' => 17, 'PA' => 17, 'PB' => 18,
            'PE' => 18, 'PI' => 18, 'PR' => 18, 'RJ' => 20, 'RN' => 18,
            'RO' => 17.5, 'RR' => 17, 'RS' => 17, 'SC' => 17, 'SE' => 18,
            'SP' => 18, 'TO' => 18
        ];
        
        return $aliquotasPadrao[$this->uf_destinatario] ?? 18.0;
    }
}
