<?php

namespace App\Services\Fiscal;

use Exception;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Tools as NFeTools;
use App\Models\Tenant\ControleNsuNfe;
use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Log;
use NFePHP\NFe\Common\Standardize as NFeStandardize;

class SefazNfeService extends BaseSefazConnectionService
{
    private NFeTools $nfeTools;
    
    public function __construct(Organization $organization, string $ambiente = 'producao')
    {
        $this->tipoDocumento = 'NFe';
        parent::__construct($organization, $ambiente);
    }

    /**
     * Configura a conexão com a SEFAZ usando os dados da organização
     */
    protected function configurar(): void
    {
        try {
            if (empty($this->organization->certificado_content)) {
                throw new Exception('Certificado digital não encontrado para a organização.');
            }

            $config = [
                'atualizacao' => date('Y-m-d H:i:s'),
                'tpAmb' => $this->ambiente === 'producao' ? 1 : 2,
                'razaosocial' => $this->organization->razao_social,
                'cnpj' => $this->organization->cnpj,
                'ie' => $this->organization->inscricao_estadual,
                'siglaUF' => $this->organization->estado ?? 'SP',
                'schemes' => 'PL_009_V4',
                'versao' => '4.00',
                'tokenIBPT' => '',
                'CSC' => '',
                'CSCid' => '',
                'aProxyConf' => [
                    'proxyIp' => '',
                    'proxyPort' => '',
                    'proxyUser' => '',
                    'proxyPass' => '',
                ],
            ];

            $certificado = Certificate::readPfx(
                base64_decode($this->organization->certificado_content),
                $this->organization->senha_certificado
            );

            // Configura ferramentas para NFe
            $this->nfeTools = new NFeTools(json_encode($config), $certificado);
        } catch (Exception $e) {
            throw new Exception('Erro ao configurar conexão com a SEFAZ NFe: ' . $e->getMessage());
        }
    }

    /**
     * Retorna a instância configurada do Tools para NFe
     */
    public function getTools(): NFeTools
    {
        return $this->nfeTools;
    }

    /**
     * Consulta documentos NFe destinados à organização
     *
     * @param int|null $nsuEspecifico NSU específico para consulta
     * @return array Resposta da SEFAZ
     */
    public function consultarDocumentosDestinados(?int $nsuEspecifico = null): array
    {
        try {
            // Configura NFe para modelo 55
            $this->nfeTools->model('55');
            $this->nfeTools->setEnvironment(1); // Produção apenas

            // Se for consulta de NSU específico, consulta apenas uma vez
            if ($nsuEspecifico !== null) {
                return $this->consultarNsuEspecifico($nsuEspecifico);
            }

            // Caso contrário, consulta todos os documentos a partir do último NSU
            return $this->consultarTodosDocumentos();
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Consulta um NSU específico
     */
    public function consultarNsuEspecifico(int $nsu): array
    {
        try {
            $response = $this->nfeTools->sefazDistDFe(0, $nsu);

            // Gerar log da consulta
            Log::info('response da sefaz NFe: ' . $response);

            $resultado = $this->processarRespostaSefaz($response);

            $this->atualizarControleNsu($nsu, $resultado['max_nsu'], $resultado['xml_content']);

            return $resultado;
        } catch (Exception $e) {
            Log::error("Erro ao consultar NSU NFe {$nsu}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Consulta todos os documentos a partir do último NSU
     */
    protected function consultarTodosDocumentos(): array
    {
        try {
            // Recupera último NSU consultado
            $controleNsu = ControleNsuNfe::where('organization_id', $this->organization->id)
                ->orderBy('ultimo_nsu', 'desc')
                ->first();

            $nsu = $controleNsu ? $controleNsu->ultimo_nsu : 0;
            $maxNSU = $nsu;
            $iCount = 0;
            $loopLimit = 50;
            $documentosProcessados = 0;
            $errors = [];

            while ($nsu <= $maxNSU) {
                $iCount++;
                if ($iCount >= $loopLimit) {
                    break;
                }

                try {
                    $response = $this->nfeTools->sefazDistDFe($nsu);
                    $resultado = $this->processarRespostaSefaz($response);

                    if (!$resultado['success']) {
                        $errors[] = $resultado['message'];
                        break;
                    }

                    $documentosProcessados += $resultado['documentos_processados'];
                    $nsu = $resultado['ultimo_nsu'];
                    $maxNSU = $resultado['max_nsu'];

                    // Atualiza controle de NSU para cada resposta bem-sucedida
                    $this->atualizarControleNsu($nsu, $maxNSU, $resultado['xml_content']);

                    // Se atingiu o máximo, finaliza
                    if ($nsu == $maxNSU) {
                        break;
                    }

                    // Aguarda 2 segundos entre consultas
                    sleep(2);
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                    break;
                }
            }

            // Verifica se há NSUs faltantes
            $resultadoVerificacao = $this->verificarNsusFaltantes();
            if (!empty($resultadoVerificacao['nsus_faltantes'])) {
                $errors[] = "NSUs faltantes encontrados e processados: " . implode(', ', $resultadoVerificacao['nsus_faltantes']);
            }

            return [
                'success' => true,
                'documentos_processados' => $documentosProcessados,
                'ultimo_nsu' => $nsu,
                'max_nsu' => $maxNSU,
                'errors' => $errors
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Atualiza o controle de NSU 
     */
    protected function atualizarControleNsu(int $ultNSU, int $maxNSU, string $xmlContent): void
    {
        ControleNsuNfe::updateOrCreate(
            [
                'organization_id' => $this->organization->id,
                'ultimo_nsu' => $ultNSU,
            ],
            [
                'max_nsu' => $maxNSU,
                'ultima_consulta' => now(),
                'xml_content' => $xmlContent,
            ]
        );
    }

    /**
     * Verifica e processa NSUs faltantes
     */
    public function verificarNsusFaltantes(): array
    {
        $ultimoControle = ControleNsuNfe::where('organization_id', $this->organization->id)
            ->orderBy('ultimo_nsu', 'desc')
            ->first();

        if (!$ultimoControle) {
            return ['success' => true, 'message' => 'Nenhum NSU processado ainda.'];
        }

        $controles = ControleNsuNfe::where('organization_id', $this->organization->id)
            ->orderBy('ultimo_nsu')
            ->get();

        if ($controles->count() <= 1) {
            return ['success' => true, 'message' => 'Nenhum NSU faltante encontrado.'];
        }

        $nsusFaltantes = [];
        $anterior = null;

        foreach ($controles as $controle) {
            if ($anterior !== null) {
                $esperado = $anterior->ultimo_nsu + 1;
                if ($controle->ultimo_nsu > $esperado) {
                    // Adiciona os NSUs faltantes entre o anterior e o atual
                    for ($i = $esperado; $i < $controle->ultimo_nsu; $i++) {
                        $nsusFaltantes[] = $i;
                    }
                }
            }
            $anterior = $controle;
        }

        if (empty($nsusFaltantes)) {
            return ['success' => true, 'message' => 'Nenhum NSU faltante encontrado.'];
        }

        // Processa os NSUs faltantes
        $processados = 0;
        foreach ($nsusFaltantes as $nsu) {
            $resultado = $this->consultarNsuEspecifico($nsu);
            if ($resultado['success']) {
                $processados++;
            }
        }

        return [
            'success' => true,
            'message' => "Processados {$processados} NSUs faltantes.",
            'nsus_faltantes' => $nsusFaltantes
        ];
    }

    /**
     * Manifesta ciência da operação para NFe
     *
     * @param string $chave Chave da NFe
     * @param string $manifestacao Tipo de manifestação (210200, 210210, 210220, 210240)
     * @param string|null $justificativa Justificativa (obrigatória para Operação não Realizada)
     * @return array
     */
    public function manifestar(string $chave, string $manifestacao, ?string $justificativa = null): array
    {
        try {
            $response = $this->nfeTools->sefazManifesta(
                chave: $chave,
                tpEvento: $manifestacao,
                nSeqEvento: 1, // Sequencial do evento
                xJust: $justificativa
            );

            // Padroniza a resposta
            $st = new NFeStandardize();
            $std = $st->toStd($response);

            return [
                'success' => true,
                'response' => $response,
                'std' => $std
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Download do XML da NFe
     *
     * @param string $chave Chave da NFe
     * @return array
     */
    public function downloadXml(string $chave): array
    {
        try {
            $response = $this->nfeTools->sefazDownload($chave);

            // Padroniza a resposta
            $st = new NFeStandardize();
            $std = $st->toStd($response);

            return [
                'success' => true,
                'response' => $response,
                'std' => $std
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
} 