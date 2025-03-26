<?php

namespace App\Interfaces;

interface ServicoLeituraDocumentoFiscal
{
    /**
     * Inicializa o serviço com o conteúdo XML
     */
    public function loadXml(string $xmlContent);
    
    /**
     * Extrai e mapeia os dados do XML para um array estruturado
     */
    public function parse();
    
    /**
     * Define a origem do XML
     */
    public function setOrigem(string $origem);
    
    /**
     * Salva ou atualiza os dados extraídos no banco de dados
     */
    public function save();
    
    /**
     * Retorna os dados extraídos do XML
     */
    public function getData(): array;
} 