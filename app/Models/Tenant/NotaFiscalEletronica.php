<?php

namespace App\Models\Tenant;

use Illuminate\Support\Facades\DB;
use App\Enums\Tenant\OrigemNfeEnum;
use App\Enums\Tenant\StatusNfeEnum;
use Illuminate\Support\Facades\Cache;
use App\Models\Tenant\Concerns\HasTags;
use App\Models\Tenant\Concerns\HasEscrituracao;
use Illuminate\Database\Eloquent\Model;
use App\Enums\Tenant\StatusManifestoNfe;
use App\Enums\Tenant\StatusManifestoNfeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class NotaFiscalEletronica extends Model
{
    use HasTags, HasUuids, HasEscrituracao;

    protected $table = 'notas_fiscais_eletronica';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $appends = ['tagging_summary'];

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

    public function products()
    {

        return $this->hasMany(Produto::class, 'nfe_id');
    }

    public function apurada()
    {
        return $this->hasOne(NfeApurada::class, 'nfe_id');
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

    
}
