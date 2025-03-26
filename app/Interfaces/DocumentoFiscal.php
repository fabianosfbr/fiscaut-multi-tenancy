<?php

namespace App\Interfaces;

interface DocumentoFiscal
{
    /**
     * Retorna a chave de acesso do documento
     */
    public function getChaveAcesso(): string;
    
    /**
     * Verifica se o documento possui referências a outros
     */
    public function possuiReferencias(): bool;
    
    /**
     * Verifica se o documento é referenciado por outros
     */
    public function ehReferenciado(): bool;
    
    /**
     * Adiciona uma referência a outro documento
     */
    public function adicionarReferencia(string $chaveAcesso, string $tipo): void;
} 