<?php

namespace App\Models\Tenant;

use Illuminate\Support\Facades\DB;
use App\Enums\Tenant\StatusNfeEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Tenant\Concerns\HasTags;
use Illuminate\Database\Eloquent\Model;
use App\Enums\Tenant\StatusManifestoNfe;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotaFiscalEletronica extends Model
{
    use HasUuids, HasTags;

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
            'data_emissao' => 'datetime',
            'data_entrada' => 'datetime',
            'status_nota' => StatusNfeEnum::class,
            'status_manifestacao' => StatusManifestoNfe::class,
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
        $result = Cache::remember('tagging_summary-' . $this->emitente_cnpj, 300, function () {

            return DB::table('organizations')
                ->join('notas_fiscais_eletronica', 'organizations.cnpj', '=', 'notas_fiscais_eletronica.destinatario_cnpj')
                ->leftJoin('tagging_tagged', 'notas_fiscais_eletronica.id', '=', 'tagging_tagged.taggable_id')
                ->leftJoin('tags', 'tags.id', '=', 'tagging_tagged.tag_id')
                ->select(
                    'tagging_tagged.tag_id',
                    'tagging_tagged.tag_name',
                    'tags.code',
                    DB::raw('COUNT(*) AS qtde')
                )
                ->where('notas_fiscais_eletronica.emitente_cnpj', $this->emitente_cnpj)
                ->where('tagging_tagged.taggable_type', 'App\Models\Tenant\NotaFiscalEletronica')
                ->groupBy('tagging_tagged.tag_id', 'tagging_tagged.tag_name')
                ->havingRaw('COUNT(*) >= 1')
                ->orderByDesc('qtde')->get()->toArray();
        });

        return  $result;
    }

}
