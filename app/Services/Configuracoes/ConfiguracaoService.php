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
        // Determinar o tipo de nota (terceiros ou propria)
        $tipo = $padrao['tipo'] ?? 'terceiros';
        if (!in_array($tipo, ['terceiros', 'propria'])) {
            $tipo = 'terceiros'; // Valor padrão
        }

        // Adicionar sufixo para diferenciar as configurações
        $categoria = "nfe_{$tipo}";

        // Log para depuração
        \Illuminate\Support\Facades\Log::info("Obtendo CFOPs de entrada NFe para tipo: {$tipo}");

        $config = OrganizacaoConfiguracao::obterConfiguracao(
            $this->organizationId,
            'entrada',
            'cfops',
            $categoria,
            $padrao
        );

        // Garantir que o tipo está definido nos dados retornados
        $config['tipo'] = $tipo;

        return $config;
    }

    /**
     * Salva configurações de CFOPs de entrada NFe
     */
    public function salvarCfopsEntradaNfe(array $configuracoes): void
    {
        // Garante que os dados têm formatação consistente
        if (!isset($configuracoes['itens'])) {
            $configuracoes['itens'] = [];
        }

        // Garante que o tipo está definido
        if (!isset($configuracoes['tipo'])) {
            $configuracoes['tipo'] = 'terceiros';
        }


        // Verificar o tipo de nota para decidir onde salvar
        $tipo = $configuracoes['tipo'];
        if (!in_array($tipo, ['terceiros', 'propria'])) {
            $tipo = 'terceiros'; // Valor padrão
        }

        // Adicionar sufixo para diferenciar as configurações
        $categoria = "nfe_{$tipo}";

        OrganizacaoConfiguracao::salvarConfiguracao(
            $this->organizationId,
            'entrada',
            $configuracoes,
            'cfops',
            $categoria
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

    /**
     * Retorna as configurações de CFOPs de saída NFe
     */
    public function obterCfopsSaidaNfe(array $padrao = []): array
    {
        // Determinar o tipo de nota (terceiros ou propria)
        $tipo = $padrao['tipo'] ?? 'terceiros';
        if (!in_array($tipo, ['terceiros', 'propria'])) {
            $tipo = 'terceiros'; // Valor padrão
        }

        // Adicionar sufixo para diferenciar as configurações
        $categoria = "nfe_saida_{$tipo}";

        // Log para depuração
        \Illuminate\Support\Facades\Log::info("Obtendo CFOPs de saída NFe para tipo: {$tipo}");

        $config = OrganizacaoConfiguracao::obterConfiguracao(
            $this->organizationId,
            'saida',
            'cfops',
            $categoria,
            $padrao
        );

        // Garantir que o tipo está definido nos dados retornados
        $config['tipo'] = $tipo;

        return $config;
    }

    /**
     * Salva configurações de CFOPs de saída NFe
     */
    public function salvarCfopsSaidaNfe(array $configuracoes): void
    {
        // Garante que os dados têm formatação consistente
        if (!isset($configuracoes['itens'])) {
            $configuracoes['itens'] = [];
        }

        // Garante que o tipo está definido
        if (!isset($configuracoes['tipo'])) {
            $configuracoes['tipo'] = 'terceiros';
        }

        // Log para depuração
        \Illuminate\Support\Facades\Log::info('ConfiguracaoService - Salvando CFOPs de saída:', [
            'tipo' => $configuracoes['tipo'],
            'num_itens' => count($configuracoes['itens'])
        ]);

        // Verificar o tipo de nota para decidir onde salvar
        $tipo = $configuracoes['tipo'];
        if (!in_array($tipo, ['terceiros', 'propria'])) {
            $tipo = 'terceiros'; // Valor padrão
        }

        // Adicionar sufixo para diferenciar as configurações
        $categoria = "nfe_saida_{$tipo}";

        OrganizacaoConfiguracao::salvarConfiguracao(
            $this->organizationId,
            'saida',
            $configuracoes,
            'cfops',
            $categoria
        );
    }

    /**
     * Retorna as configurações de CFOPs de entrada CTe
     */
    public function obterCfopsEntradaCte(array $padrao = []): array
    {
        // Determinar o tipo de nota (terceiros ou propria)
        $tipo = $padrao['tipo'] ?? 'entrada';
        if (!in_array($tipo, ['entrada', 'saida'])) {
            $tipo = 'entrada'; // Valor padrão
        }

        // Adicionar sufixo para diferenciar as configurações
        $categoria = "cte_{$tipo}";

        $config = OrganizacaoConfiguracao::obterConfiguracao(
            $this->organizationId,
            'entrada',
            'cfops',
            $categoria,
            $padrao
        );

        // Garantir que o tipo está definido nos dados retornados
        $config['tipo'] = $tipo;


        return $config;
    }

    /**
     * Salva configurações de CFOPs de entrada CTe
     */
    public function salvarCfopsEntradaCte(array $configuracoes): void
    {
        // Garante que os dados têm formatação consistente
        if (!isset($configuracoes['itens'])) {
            $configuracoes['itens'] = [];
        }

        // Garante que o tipo está definido
        if (!isset($configuracoes['tipo'])) {
            $configuracoes['tipo'] = 'entrada';
        }

          // Verificar o tipo de nota para decidir onde salvar
          $tipo = $configuracoes['tipo'];
        if (!in_array($tipo, ['entrada', 'saida'])) {
            $tipo = 'entrada'; // Valor padrão
        }

        // Adicionar sufixo para diferenciar as configurações
        $categoria = "cte_{$tipo}";


        OrganizacaoConfiguracao::salvarConfiguracao(
            $this->organizationId,
            'entrada',
            $configuracoes,
            'cfops',
            $categoria
        );
    }

    /**
     * Retorna as configurações de CFOPs de saída CTe
     */
    public function obterCfopsSaidaCte(array $padrao = []): array
    {
        // Log para depuração
        \Illuminate\Support\Facades\Log::info("Obtendo CFOPs de saída CTe");

        $config = OrganizacaoConfiguracao::obterConfiguracao(
            $this->organizationId,
            'saida',
            'cfops',
            'cte_saida',
            $padrao
        );

        return $config;
    }

    /**
     * Salva configurações de CFOPs de saída CTe
     */
    public function salvarCfopsSaidaCte(array $configuracoes): void
    {
        // Garante que os dados têm formatação consistente
        if (!isset($configuracoes['itens'])) {
            $configuracoes['itens'] = [];
        }

        // Log para depuração
        \Illuminate\Support\Facades\Log::info('ConfiguracaoService - Salvando CFOPs de saída CTe:', [
            'num_itens' => count($configuracoes['itens'])
        ]);

        OrganizacaoConfiguracao::salvarConfiguracao(
            $this->organizationId,
            'saida',
            $configuracoes,
            'cfops',
            'cte_saida'
        );
    }
}
