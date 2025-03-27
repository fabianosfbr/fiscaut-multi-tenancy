<?php

namespace App\Services\Configuracoes;

use App\Models\Tenant\OrganizacaoConfiguracao;

class ConfiguracaoService
{
    protected string $organizationId;
    
    public function __construct(string $organizationId)
    {
        $this->organizationId = $organizationId;
    }
    
    /**
     * Retorna as configurações gerais
     */
    public function obterConfiguracoesGerais(array $padrao = []): array
    {
        $configPadrao = [
            'nfe_classificacao_data_entrada' => true,
            'manifestacao_automatica' => false,
            'mostrar_codigo_etiqueta' => true,
            'icms_credito_cfop_1401' => false,
            'cfop_verificar_uf' => true,
        ];
        
        $configPadrao = array_merge($configPadrao, $padrao);
        
        return OrganizacaoConfiguracao::obterConfiguracao(
            $this->organizationId,
            'geral',
            null,
            null,
            $configPadrao
        );
    }
    
    /**
     * Salva configurações gerais
     */
    public function salvarConfiguracoesGerais(array $configuracoes): void
    {
        OrganizacaoConfiguracao::salvarConfiguracao(
            $this->organizationId,
            'geral',
            $configuracoes
        );
    }
    
    /**
     * Atualiza configurações gerais específicas
     */
    public function atualizarConfiguracoesGerais(array $configuracoes): void
    {
        OrganizacaoConfiguracao::atualizarConfiguracao(
            $this->organizationId,
            'geral',
            $configuracoes
        );
    }
    
    /**
     * Retorna as configurações de entrada NFe
     */
    public function obterConfiguracoesEntradaNfe(array $padrao = []): array
    {
        return OrganizacaoConfiguracao::obterConfiguracao(
            $this->organizationId,
            'entrada',
            'configuracoes',
            'nfe',
            $padrao
        );
    }
    
    /**
     * Salva configurações de entrada NFe
     */
    public function salvarConfiguracoesEntradaNfe(array $configuracoes): void
    {
        OrganizacaoConfiguracao::salvarConfiguracao(
            $this->organizationId,
            'entrada',
            $configuracoes,
            'configuracoes',
            'nfe'
        );
    }
    
    /**
     * Retorna as configurações de saída NFe
     */
    public function obterConfiguracoesSaidaNfe(array $padrao = []): array
    {
        return OrganizacaoConfiguracao::obterConfiguracao(
            $this->organizationId,
            'saida',
            'configuracoes',
            'nfe',
            $padrao
        );
    }
    
    /**
     * Salva configurações de saída NFe
     */
    public function salvarConfiguracoesSaidaNfe(array $configuracoes): void
    {
        OrganizacaoConfiguracao::salvarConfiguracao(
            $this->organizationId,
            'saida',
            $configuracoes,
            'configuracoes',
            'nfe'
        );
    }
    
    /**
     * Retorna as configurações de CFOPs de entrada NFe
     */
    public function obterCfopsEntradaNfe(array $padrao = []): array
    {
        return OrganizacaoConfiguracao::obterConfiguracao(
            $this->organizationId,
            'entrada',
            'cfops',
            'nfe',
            $padrao
        );
    }
    
    /**
     * Salva configurações de CFOPs de entrada NFe
     */
    public function salvarCfopsEntradaNfe(array $configuracoes): void
    {
        OrganizacaoConfiguracao::salvarConfiguracao(
            $this->organizationId,
            'entrada',
            $configuracoes,
            'cfops',
            'nfe'
        );
    }
    
    /**
     * Retorna as configurações de acumuladores de entrada NFe
     */
    public function obterAcumuladoresEntradaNfe(array $padrao = []): array
    {
        return OrganizacaoConfiguracao::obterConfiguracao(
            $this->organizationId,
            'entrada',
            'acumuladores',
            'nfe',
            $padrao
        );
    }
    
    /**
     * Salva configurações de acumuladores de entrada NFe
     */
    public function salvarAcumuladoresEntradaNfe(array $configuracoes): void
    {
        OrganizacaoConfiguracao::salvarConfiguracao(
            $this->organizationId,
            'entrada',
            $configuracoes,
            'acumuladores',
            'nfe'
        );
    }
    
    /**
     * Retorna as configurações de produtos genéricos
     */
    public function obterProdutosGenericos(array $padrao = []): array
    {
        return OrganizacaoConfiguracao::obterConfiguracao(
            $this->organizationId,
            'entrada',
            'produtos_genericos',
            null,
            $padrao
        );
    }
    
    /**
     * Salva configurações de produtos genéricos
     */
    public function salvarProdutosGenericos(array $configuracoes): void
    {
        OrganizacaoConfiguracao::salvarConfiguracao(
            $this->organizationId,
            'entrada',
            $configuracoes,
            'produtos_genericos'
        );
    }
    
    /**
     * Helper para acessar configuração específica
     */
    public function obterValor(string $tipo, ?string $subtipo, ?string $categoria, string $chave, $valorPadrao = null)
    {
        $config = OrganizacaoConfiguracao::obterConfiguracao(
            $this->organizationId,
            $tipo,
            $subtipo,
            $categoria
        );
        
        return $config[$chave] ?? $valorPadrao;
    }
} 