<?php

namespace App\Models\Tenant;

use Illuminate\Support\Facades\DB;
use App\Enums\Tenant\OrigemNfeEnum;
use App\Enums\Tenant\StatusNfeEnum;
use Illuminate\Support\Facades\Cache;
use App\Models\Tenant\Concerns\HasTags;
use App\Models\Tenant\Concerns\HasEscrituracao;
use App\Models\Tenant\Concerns\HasDocumentoReferencias;
use Illuminate\Database\Eloquent\Model;
use App\Enums\Tenant\StatusManifestoNfe;
use App\Enums\Tenant\StatusManifestoNfeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Interfaces\DocumentoFiscal;

class NotaFiscalEletronica extends Model implements DocumentoFiscal
{
    use HasTags, HasUuids, HasEscrituracao, HasDocumentoReferencias;

    protected $table = 'notas_fiscais_eletronica';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $appends = ['tagging_summary'];

    protected $with = ['itens', 'impostos'];

    protected function casts(): array
    {
        return [
            'aut_xml' => 'array',
            'carta_correcao' => 'array',
            'pagamento' => 'array',
            'cobranca' => 'array',
            'cfops' => 'array',

            'data_entrada' => 'datetime',
            'status_nota' => StatusNfeEnum::class,
            'status_manifestacao' => StatusManifestoNfeEnum::class,
            'origem' => OrigemNfeEnum::class,

            'data_emissao' => 'datetime',
            'valor_total' => 'decimal:2',
            'valor_produtos' => 'decimal:2',
            'valor_base_icms' => 'decimal:2',
            'valor_icms' => 'decimal:2',
            'valor_icms_desonerado' => 'decimal:2',
            'valor_fcp' => 'decimal:2',
            'valor_base_icms_st' => 'decimal:2',
            'valor_icms_st' => 'decimal:2',
            'valor_fcp_st' => 'decimal:2',
            'valor_base_ipi' => 'decimal:2',
            'valor_ipi' => 'decimal:2',
            'valor_base_pis' => 'decimal:2',
            'valor_pis' => 'decimal:2',
            'valor_base_cofins' => 'decimal:2',
            'valor_cofins' => 'decimal:2',
            'valor_aproximado_tributos' => 'decimal:2',
        ];
    }




    public function getTaggingSummaryAttribute()
    {
        $result = Cache::remember('tagging_summary_' . $this->cnpj_emitente, now()->addDay(), function () {
            return DB::table('organizations')
            ->join('notas_fiscais_eletronica', 'organizations.cnpj', '=', 'notas_fiscais_eletronica.cnpj_destinatario')
            ->leftJoin('tagging_tagged', 'notas_fiscais_eletronica.id', '=', 'tagging_tagged.taggable_id')
            ->leftJoin('tags', 'tags.id', '=', 'tagging_tagged.tag_id')
            ->select(
                'tagging_tagged.tag_id',
                'tagging_tagged.tag_name',
                'tags.code',
                DB::raw('COUNT(*) AS qtde')
            )
            ->where('notas_fiscais_eletronica.cnpj_emitente', $this->cnpj_emitente)
            ->where('tagging_tagged.taggable_type', $this->getMorphClass())
            ->groupBy('tagging_tagged.tag_id', 'tagging_tagged.tag_name')
            ->havingRaw('COUNT(*) >= 1')
            ->orderByDesc('qtde')->get()->toArray();
        });
    
 

        return $result;
    }


    public function itens()
    {
        return $this->hasMany(NotaFiscalEletronicaItem::class, 'nfe_id');
    }

    public function impostos()
    {
        return $this->hasOne(NotaFiscalEletronicaImposto::class, 'nfe_id');
    }

    // Método auxiliar para calcular o total de impostos
    public function getTotalImpostosAttribute(): float
    {
        return $this->valor_icms +
            $this->valor_icms_st +
            $this->valor_ipi +
            $this->valor_pis +
            $this->valor_cofins +
            $this->valor_fcp +
            $this->valor_fcp_st;
    }

    public function historicos()
    {
        return $this->hasMany(NotaFiscalEletronicaHistorico::class, 'nfe_id');
    }

    /**
     * Referências que esta nota faz a outras notas
     */
    public function referenciasFeitas()
    {
        return $this->morphMany(DocumentoReferencia::class, 'documento_origem');
    }

    /**
     * Referências que outras notas fazem a esta nota
     */
    public function referenciasRecebidas()
    {
        return $this->morphMany(DocumentoReferencia::class, 'documento_referenciado');
    }

    public function getEnderecoEmitenteCompletoAttribute(): string
    {
        $endereco = $this->logradouro_emitente;
        if ($this->numero_emitente) $endereco .= ", {$this->numero_emitente}";
        if ($this->complemento_emitente) $endereco .= " - {$this->complemento_emitente}";
        if ($this->bairro_emitente) $endereco .= " - {$this->bairro_emitente}";

        return $endereco;
    }

    public function getEnderecoDestinatarioCompletoAttribute(): string
    {
        $endereco = $this->logradouro_destinatario;
        if ($this->numero_destinatario) $endereco .= ", {$this->numero_destinatario}";
        if ($this->complemento_destinatario) $endereco .= " - {$this->complemento_destinatario}";
        if ($this->bairro_destinatario) $endereco .= " - {$this->bairro_destinatario}";

        return $endereco;
    }

    public function getCfopsAttribute(): string
    {
        return $this->itens()
            ->select('cfop')
            ->distinct()
            ->pluck('cfop')
            ->sort()
            ->implode(', ');
    }

    public function retag(string $tag)
    {
        $this->untag();
        $this->tag($tag, $this->valor_total);
    }

    public function scopeEntradasTerceiros($query, $organization = null)
    {
        $organization = $organization ?? getOrganizationCached();
        return $query->where('cnpj_destinatario', $organization->cnpj)
                    ->where('cnpj_emitente', '<>', $organization->cnpj)
                    ->where('tipo', 1);
    }

    public function scopeEntradasProprias($query, $organization = null)
    {
        $organization = $organization ?? getOrganizationCached();

        return $query->where('cnpj_emitente', $organization->cnpj)
                    ->where('tipo', '0');
    }

    public function scopeEntradasPropriasTerceiros($query, $organization = null)
    {
        $organization = $organization ?? getOrganizationCached();
        return $query->where('cnpj_destinatario', $organization->cnpj)
                    ->where('cnpj_emitente', '<>', $organization->cnpj)
                    ->where('tipo', '0');
    }

    /**
     * Calcula o diferencial de alíquota (DIFAL) para cada produto da nota
     * 
     * @return array Array contendo os dados do DIFAL para cada produto
     */
    public function calcularDifalProdutos(): array
    {
        $resultado = [];
               
        // Verifica se existem itens na nota
        if (!$this->itens || $this->itens->isEmpty()) {
            return $resultado;
        }
        
        foreach ($this->itens as $item) {
            // Obtém os valores necessários para o cálculo
            $valorContabil = $item->valor_total ?? 0;
            $baseCalculo = $item->base_calculo_icms ?? 0;
            
            // Pula itens sem base de cálculo
            if ($baseCalculo <= 0) {
                continue;
            }
            
            // Alíquota de origem - pode vir do produto ou da tabela
            $aliquotaOrigem = $this->obterAliquotaOrigem($item);
            
            // Alíquota de destino (interna do estado de destino)
            $aliquotaDestino = $this->obterAliquotaDestino();
                        
            // Calcula o DIFAL
            $valorDifal = 0;
            $valorIcms = $item->valor_icms ?? 0;
         
            if ($aliquotaDestino > $aliquotaOrigem) {
                // Cálculo do DIFAL: Base de Cálculo * (Alíquota Destino - Alíquota Origem)
                $valorDifal = $baseCalculo * (($aliquotaDestino - $aliquotaOrigem) / 100);
            }
            
            $resultado[] = [
                'item_id' => $item->id,
                'codigo' => $item->codigo ?? '-',
                'descricao' => $item->descricao ?? 'Sem descrição',
                'ncm' => $item->ncm ?? '-',
                'cfop' => $item->cfop ?? '-',
                'valor_contabil' => $valorContabil,
                'base_calculo' => $baseCalculo,
                'aliquota_origem' => $aliquotaOrigem,
                'aliquota_destino' => $aliquotaDestino,
                'valor_icms' => $valorIcms,
                'valor_difal' => round($valorDifal, 2)
            ];
        }
   
        return $resultado;
    }
    
    /**
     * Obtém a alíquota de origem para o item
     * 
     * @param object $item Item da nota fiscal
     * @return float Alíquota de origem a ser usada no cálculo
     */
    private function obterAliquotaOrigem($item): float
    {
        // Primeiro verifica se o item já tem uma alíquota definida
        if (!empty($item->aliquota_icms)) {
            return (float) $item->aliquota_icms;
        }
        
        // Verifica se UF de origem e destino são válidas
        if (empty($this->uf_emitente) || empty($this->uf_destinatario)) {
            return 12.0; // Valor padrão se não tiver UFs válidas
        }
        
        // Se não tiver, busca na tabela de alíquotas interestaduais
        $aliquotasInterestaduais = config('aliquotas_icms.valor_icms');
        
        if ($aliquotasInterestaduais && isset($aliquotasInterestaduais[$this->uf_emitente]) && isset($aliquotasInterestaduais['UF'])) {
            // Busca o índice da UF de destino no array de UFs
            $indexUfDestino = array_search($this->uf_destinatario, $aliquotasInterestaduais['UF']);
            
            // Se encontrar, pega a alíquota definida para esta origem-destino
            if ($indexUfDestino !== false && isset($aliquotasInterestaduais[$this->uf_emitente][$indexUfDestino])) {
                return (float) $aliquotasInterestaduais[$this->uf_emitente][$indexUfDestino];
            }
        }
        
        // Alíquotas padrão para operações interestaduais baseadas na região
        $regioesSul = ['PR', 'RS', 'SC'];
        $regioesSudeste = ['SP', 'RJ', 'ES', 'MG'];
        
        // Se o emitente for do Sul ou Sudeste e o destinatário for de outras regiões, usa 7%
        if (in_array($this->uf_emitente, array_merge($regioesSul, $regioesSudeste)) && 
            !in_array($this->uf_destinatario, array_merge($regioesSul, $regioesSudeste))) {
            return 7.0;
        }
        
        // Para outras combinações, usa 12%
        return 12.0;
    }
    
    /**
     * Obtém a alíquota de destino (interna do estado destinatário)
     * 
     * @return float Alíquota interna do estado de destino
     */
    private function obterAliquotaDestino(): float
    {
        // Verifica se a UF de destino é válida
        if (empty($this->uf_destinatario)) {
            return 18.0; // Valor padrão se não tiver UF de destino válida
        }
        
        // Busca a alíquota interna diretamente na tabela de alíquotas
        $aliquotasInterestaduais = config('aliquotas_icms.valor_icms');
        
        // Se a UF de destino estiver definida e for igual à UF emitente (operação interna),
        // pega o valor da diagonal principal da tabela
        if (isset($aliquotasInterestaduais[$this->uf_destinatario]) && isset($aliquotasInterestaduais['UF'])) {
            $indexUfDestino = array_search($this->uf_destinatario, $aliquotasInterestaduais['UF']);
            
            if ($indexUfDestino !== false && isset($aliquotasInterestaduais[$this->uf_destinatario][$indexUfDestino])) {
                // Pega a alíquota interna (operação dentro do mesmo estado)
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
    
    /**
     * Calcula o valor total do DIFAL para a nota
     * 
     * @return float Valor total do DIFAL
     */
    public function calcularTotalDifal(): float
    {
        $difalProdutos = $this->calcularDifalProdutos();
        
        return array_sum(array_column($difalProdutos, 'valor_difal'));
    }

}
