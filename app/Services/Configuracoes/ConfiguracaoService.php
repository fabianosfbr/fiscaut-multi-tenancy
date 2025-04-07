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
        // Determinar o tipo de nota (terceiro ou propria)
        $tipo = $padrao['tipo'] ?? 'terceiro';
        if (!in_array($tipo, ['terceiro', 'propria'])) {
            $tipo = 'terceiro'; // Valor padrão
        }

        // Adicionar sufixo para diferenciar as configurações
        $categoria = "nfe_{$tipo}";

 
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
            $configuracoes['tipo'] = 'terceiro';
        }


        // Verificar o tipo de nota para decidir onde salvar
        $tipo = $configuracoes['tipo'];
        if (!in_array($tipo, ['terceiro', 'propria'])) {
            $tipo = 'terceiro'; // Valor padrão
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
    public function obterAcumuladoresTerceiroNfe(array $padrao = []): array
    {
        return OrganizacaoConfiguracao::obterConfiguracao(
            $this->organizationId,
            'entrada',
            'acumuladores',
            'nfe_terceiro',
            $padrao
        );
    }

    /**
     * Salva configurações de acumuladores de entrada NFe
     */
    public function salvarAcumuladoresTerceiroNfe(array $configuracoes): void
    {
        OrganizacaoConfiguracao::salvarConfiguracao(
            $this->organizationId,
            'entrada',
            $configuracoes,
            'acumuladores',
            'nfe_terceiro'
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
        // Determinar o tipo de nota (terceiro ou propria)
        $tipo = $padrao['tipo'] ?? 'terceiro';
        if (!in_array($tipo, ['terceiro', 'propria'])) {
            $tipo = 'terceiro'; // Valor padrão
        }

        // Adicionar sufixo para diferenciar as configurações
        $categoria = "nfe_saida_{$tipo}";

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
            $configuracoes['tipo'] = 'terceiro';
        }


        // Verificar o tipo de nota para decidir onde salvar
        $tipo = $configuracoes['tipo'];
        if (!in_array($tipo, ['terceiro', 'propria'])) {
            $tipo = 'terceiro'; // Valor padrão
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
        // Determinar o tipo de nota (terceiro ou propria)
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

        OrganizacaoConfiguracao::salvarConfiguracao(
            $this->organizationId,
            'saida',
            $configuracoes,
            'cfops',
            'cte_saida'
        );
    }

    /**
     * Retorna as configurações de acumuladores de entrada NFe para notas próprias
     */
    public function obterAcumuladoresNfePropria(array $padrao = []): array
    {
        return OrganizacaoConfiguracao::obterConfiguracao(
            $this->organizationId,
            'entrada',
            'acumuladores',
            'nfe_propria',
            $padrao
        );
    }

    /**
     * Salva configurações de acumuladores de entrada NFe para notas próprias
     */
    public function salvarAcumuladoresNfePropria(array $configuracoes): void
    {
        OrganizacaoConfiguracao::salvarConfiguracao(
            $this->organizationId,
            'entrada',
            $configuracoes,
            'acumuladores',
            'nfe_propria'
        );
    }

    /**
     * Retorna as configurações de acumuladores de entrada CTe
     */
    public function obterAcumuladoresCteEntrada(array $padrao = []): array
    {
        return OrganizacaoConfiguracao::obterConfiguracao(
            $this->organizationId,
            'entrada',
            'acumuladores',
            'cte_entrada',
            $padrao
        );
    }

    /**
     * Salva configurações de acumuladores de entrada CTe
     */
    public function salvarAcumuladoresCteEntrada(array $configuracoes): void
    {
        OrganizacaoConfiguracao::salvarConfiguracao(
            $this->organizationId,
            'entrada',
            $configuracoes,
            'acumuladores',
            'cte_entrada'
        );
    }

    /**
     * Retorna as configurações de acumuladores de saída CTe
     */
    public function obterAcumuladoresCteSaida(array $padrao = []): array
    {
        return OrganizacaoConfiguracao::obterConfiguracao(
            $this->organizationId,
            'saida',
            'acumuladores',
            'cte_saida',
            $padrao
        );
    }

    /**
     * Salva configurações de acumuladores de saída CTe
     */
    public function salvarAcumuladoresCteSaida(array $configuracoes): void
    {
        OrganizacaoConfiguracao::salvarConfiguracao(
            $this->organizationId,
            'saida',
            $configuracoes,
            'acumuladores',
            'cte_saida'
        );
    }

    /**
     * Retorna as configurações de acumuladores de saída NFe
     */
    public function obterAcumuladoresNfeSaida(array $padrao = []): array
    {
        return OrganizacaoConfiguracao::obterConfiguracao(
            $this->organizationId,
            'saida',
            'acumuladores',
            'nfe_saida',
            $padrao
        );
    }

    /**
     * Salva configurações de acumuladores de saída NFe
     */
    public function salvarAcumuladoresNfeSaida(array $configuracoes): void
    {
        OrganizacaoConfiguracao::salvarConfiguracao(
            $this->organizationId,
            'saida',
            $configuracoes,
            'acumuladores',
            'nfe_saida'
        );
    }

    /**
     * Retorna as configurações de impostos
     */
    public function obterConfiguracoesImpostos(array $padrao = []): array
    {
        return OrganizacaoConfiguracao::obterConfiguracao(
            $this->organizationId,
            'entrada',
            'impostos',
            'configuracoes',
            $padrao
        );
    }

    /**
     * Salva configurações de impostos
     */
    public function salvarConfiguracoesImpostos(array $configuracoes): void
    {
        // Garante que os dados têm formatação consistente
        if (!isset($configuracoes['itens'])) {
            $configuracoes['itens'] = [];
        }

        OrganizacaoConfiguracao::salvarConfiguracao(
            $this->organizationId,
            'entrada',
            $configuracoes,
            'impostos',
            'configuracoes'
        );
    }
}
