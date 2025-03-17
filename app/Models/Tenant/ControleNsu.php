<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControleNsu extends Model
{
    use HasUuids;
    
    protected $table = 'controle_nsu';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'ultimo_nsu',
        'max_nsu',
        'ultima_consulta',
        'xml_content'
    ];

    protected $casts = [
        'ultima_consulta' => 'datetime',
        'ultimo_nsu' => 'integer',
        'max_nsu' => 'integer'
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
} 