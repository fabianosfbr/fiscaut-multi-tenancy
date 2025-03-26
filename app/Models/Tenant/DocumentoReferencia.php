<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentoReferencia extends Model
{
    protected $table = 'documento_referencias';

    protected $fillable = [
        'documento_origem_type',
        'documento_origem_id',
        'chave_acesso_origem',
        'documento_referenciado_type',
        'documento_referenciado_id',
        'chave_acesso_referenciada',
        'tipo_referencia',
    ];

    /**
     * Documento que faz a referência
     */
    public function documentoOrigem(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Documento que é referenciado
     */
    public function documentoReferenciado(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Método estático para criar uma nova referência entre documentos
     */
    public static function criarReferencia(
        Model $documentoOrigem, 
        string $chaveAcessoReferenciada, 
        string $tipo = 'NFE', 
        ?Model $documentoReferenciado = null
    ): self {
        // Verifica se já existe uma referência idêntica
        $referencia = static::where([
            'documento_origem_type' => get_class($documentoOrigem),
            'documento_origem_id' => $documentoOrigem->id,
            'chave_acesso_referenciada' => $chaveAcessoReferenciada,
        ])->first();
        
        // Se já existe, retorna a referência existente
        if ($referencia) {
            return $referencia;
        }
        
        // Caso contrário, cria uma nova referência
        return static::create([
            'documento_origem_type' => get_class($documentoOrigem),
            'documento_origem_id' => $documentoOrigem->id,
            'chave_acesso_origem' => $documentoOrigem->chave_acesso,
            'documento_referenciado_type' => $documentoReferenciado ? get_class($documentoReferenciado) : null,
            'documento_referenciado_id' => $documentoReferenciado ? $documentoReferenciado->id : null,
            'chave_acesso_referenciada' => $chaveAcessoReferenciada,
            'tipo_referencia' => $tipo,
        ]);
    }
} 