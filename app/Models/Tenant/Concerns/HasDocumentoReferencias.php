<?php

namespace App\Models\Tenant\Concerns;

use App\Models\Tenant\DocumentoReferencia;

/**
 * Trait para modelos que podem ter referências a outros documentos
 * 
 * O modelo que usar este trait deve ter:
 * - Propriedade chave_acesso
 * - Propriedade id
 */
trait HasDocumentoReferencias
{
    /**
     * Retorna a chave de acesso do documento
     */
    public function getChaveAcesso(): string
    {
        return $this->chave_acesso;
    }
    
    /**
     * Referências que este documento faz a outros
     */
    public function referenciasFeitas()
    {
        return $this->morphMany(DocumentoReferencia::class, 'documento_origem');
    }

    /**
     * Referências que outros documentos fazem a este
     */
    public function referenciasRecebidas()
    {
        return $this->morphMany(DocumentoReferencia::class, 'documento_referenciado');
    }

    /**
     * Verifica se o documento possui referências a outros
     */
    public function possuiReferencias(): bool
    {
        return $this->referenciasFeitas()->exists();
    }

    /**
     * Verifica se o documento é referenciado por outros
     */
    public function ehReferenciado(): bool
    {
        return $this->referenciasRecebidas()->exists();
    }

    /**
     * Adiciona uma referência deste documento para outro
     */
    public function adicionarReferencia(string $chaveAcesso, string $tipo = 'NFE'): void
    {
        // Busca o documento referenciado se ele existir no sistema
        $classeAtual = get_class($this);
        $documentoReferenciado = $classeAtual::where('chave_acesso', $chaveAcesso)->first();
        
        // Cria a referência
        DocumentoReferencia::criarReferencia($this, $chaveAcesso, $tipo, $documentoReferenciado);
    }
} 