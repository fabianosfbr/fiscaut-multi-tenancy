<?php

namespace App\Services\Fiscal;

use App\Models\Tenant\Organization;
use App\Models\Tenant\ControleNsu;
use App\Models\Tenant\ResumoNfe;
use App\Models\Tenant\EventoNfe;
use App\Services\Tenant\Xml\XmlNfeReaderService;
use Carbon\Carbon;
use DOMDocument;
use Exception;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Tools as NFeTools;
use NFePHP\CTe\Tools as CTeTools;
use NFePHP\NFe\Common\Standardize as NFeStandardize;
use NFePHP\CTe\Common\Standardize as CTeStandardize;
use Illuminate\Support\Facades\Log;

class SefazConnectionService
{
    private Organization $organization;
    private NFeTools $nfeTools;
    private CTeTools $cteTools;
    private string $ambiente;

    public function __construct(Organization $organization, string $ambiente = 'producao')
    {
        $this->organization = $organization;
        $this->ambiente = $ambiente;
        $this->configurar();
    }

    /**
     * Configura a conexão com a SEFAZ usando os dados da organização
     *
     * @throws Exception
     */
    private function configurar(): void
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

            // Configura ferramentas para CTe
            $configCte = $config;
            $configCte['schemes'] = 'PL_CTe_400';
            $configCte['versao'] = '4.00';
            $this->cteTools = new CTeTools(json_encode($configCte), $certificado);
        } catch (Exception $e) {
            throw new Exception('Erro ao configurar conexão com a SEFAZ: ' . $e->getMessage());
        }
    }

    /**
     * Retorna a instância configurada do Tools para NFe
     */
    public function getNFeTools(): NFeTools
    {
        return $this->nfeTools;
    }

    /**
     * Retorna a instância configurada do Tools para CTe
     */
    public function getCTeTools(): CTeTools
    {
        return $this->cteTools;
    }

    /**
     * Consulta documentos NFe destinados à organização
     *
     * @param int|null $nsuEspecifico NSU específico para consulta
     * @param int $loopLimit Limite de loops para consulta (default 50)
     * @return array Resposta da SEFAZ
     */
    public function consultarNFeDestinadas(?int $nsuEspecifico = null): array
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
     * Processa a resposta da SEFAZ e os documentos do lote
     */
    private function processarRespostaSefaz(string $response): array
    {
        $dom = new DOMDocument();
        $dom->loadXML($response);
        $node = $dom->getElementsByTagName('retDistDFeInt')->item(0);


        // Extrai informações do retorno
        $cStat = $node->getElementsByTagName('cStat')->item(0)->nodeValue;
        $xMotivo = $node->getElementsByTagName('xMotivo')->item(0)->nodeValue;
        $ultNSU = $node->getElementsByTagName('ultNSU')->item(0)->nodeValue;
        $maxNSU = $node->getElementsByTagName('maxNSU')->item(0)->nodeValue;
        $lote = $node->getElementsByTagName('loteDistDFeInt')->item(0);

        // Verifica status de bloqueio ou sem documentos
        if (in_array($cStat, ['137', '656'])) {
            return [
                'success' => false,
                'message' => "Status $cStat: $xMotivo",
                'ultimo_nsu' => $ultNSU,
                'max_nsu' => $maxNSU,
                'status' => $cStat,
                'motivo' => $xMotivo,
                'documentos_processados' => 0
            ];
        }

        $documentosProcessados = 0;

        if (!empty($lote)) {
            $docs = $lote->getElementsByTagName('docZip');
            foreach ($docs as $doc) {
                $schema = $doc->getAttribute('schema');
                $content = gzdecode(base64_decode($doc->nodeValue));

                // Processa documento baseado no schema
                switch (true) {
                    case str_contains($schema, 'resNFe'):
                        $this->processarResumoNFe($content);
                        break;

                    case str_contains($schema, 'procNFe'):
                        $this->processarNFe($content);
                        break;

                    case str_contains($schema, 'resEvento'):
                        $this->processarResumoEvento($content);
                        break;

                    case str_contains($schema, 'procEventoNFe'):
                        $this->processarEvento($content);
                        break;
                }

                $documentosProcessados++;
            }
        }

        return [
            'success' => true,
            'ultimo_nsu' => $ultNSU,
            'max_nsu' => $maxNSU,
            'status' => $cStat,
            'motivo' => $xMotivo,
            'xml_content' => $content,
            'documentos_processados' => $documentosProcessados
        ];
    }

    /**
     * Consulta um NSU específico
     */
    private function consultarNsuEspecifico(int $nsu): array
    {
        try {
             $response = $this->nfeTools->sefazDistDFe(0, $nsu);
           
            $resultado = $this->processarRespostaSefaz($response);
        
            $this->atualizarControleNsu($nsu, $resultado['max_nsu'], $resultado['xml_content']);

            return $resultado;

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Consulta todos os documentos a partir do último NSU
     */
    private function consultarTodosDocumentos(): array
    {
        try {
            // Recupera último NSU consultado
            $controleNsu = ControleNsu::where('organization_id', $this->organization->id)->first();
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

            // Atualiza controle de NSU
            $this->atualizarControleNsu($nsu, $maxNSU, $resultado['xml_content']);

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
     * Processa o resumo da NFe
     */
    private function processarResumoNFe(string $content): void
    {
        try {
            $st = new NFeStandardize();
            $std = $st->toStd($content);

            ResumoNfe::updateOrCreate(
                ['chave' => $std->chNFe],
                [
                    'organization_id' => $this->organization->id,
                    'cnpj_emitente' => $std->CNPJ,
                    'nome_emitente' => $std->xNome,
                    'ie_emitente' => $std->IE,
                    'data_emissao' => Carbon::parse($std->dhEmi),
                    'valor_total' => $std->vNF,
                    'situacao' => $std->cSitNFe,
                    'xml_resumo' => $content,
                    'necessita_manifestacao' => true
                ]
            );
        } catch (Exception $e) {
            Log::error('Erro ao processar resumo NFe: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Processa a NFe completa
     */
    private function processarNFe(string $content): void
    {
        try {
            $xmlReader = new XmlNfeReaderService();
            $xmlReader->loadXml($content)
                ->parse()
                ->setOrigem('SEFAZ')
                ->save();
        } catch (Exception $e) {
            Log::error('Erro ao processar NFe: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Processa o resumo do evento
     */
    private function processarResumoEvento(string $content): void
    {
        try {
            $st = new NFeStandardize();
            $std = $st->toStd($content);


            EventoNfe::create([
                'organization_id' => $this->organization->id,
                'chave_nfe' => $std->chNFe,
                'tipo_evento' => $std->tpEvento,
                'numero_sequencial' => $std->nSeqEvento,
                'data_evento' => Carbon::parse($std->dhEvento),
                'xml_evento' => $content,
                'protocolo' => $std->nProt ?? null,
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao processar resumo evento: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Processa o evento completo
     */
    private function processarEvento(string $content): void
    {
        try {
            $st = new NFeStandardize();
            $std = $st->toStd($content);

            // Acessa os dados do evento dentro da estrutura correta
            $evento = $std->evento;
            $retEvento = $std->retEvento;

            EventoNfe::updateOrCreate(
                [
                    'chave_nfe' => $evento->infEvento->chNFe,
                    'tipo_evento' => $evento->infEvento->tpEvento,
                    'numero_sequencial' => $evento->infEvento->nSeqEvento
                ],
                [
                    'organization_id' => $this->organization->id,
                    'data_evento' => Carbon::parse($evento->infEvento->dhEvento),
                    'xml_evento' => $content,
                    'protocolo' => $retEvento->infEvento->nProt,
                    'status_sefaz' => $retEvento->infEvento->cStat,
                    'motivo' => $retEvento->infEvento->xMotivo
                ]
            );
        } catch (Exception $e) {
            Log::error('Erro ao processar evento: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Atualiza o controle de NSU 
     */
    private function atualizarControleNsu(int $ultNSU, int $maxNSU, string $xmlContent): void
    {
        ControleNsu::updateOrCreate(
            [
                'organization_id' => $this->organization->id,
                'ultimo_nsu' => $ultNSU,
            ],
            [
                'max_nsu' => $maxNSU,
                'ultima_consulta' => now(),
                'xml_content' => $xmlContent
            ]
        );
    }

    /**
     * Consulta documentos CTe destinados à organização
     *
     * @param int $nsu Último NSU consultado
     * @return array Resposta da SEFAZ
     */
    public function consultarCTeDestinados(int $nsu = 0): array
    {
        try {
            $response = $this->cteTools->sefazDistDFe(
                $this->organization->cnpj,
                $nsu
            );

            // Padroniza a resposta
            $st = new CTeStandardize();
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
     * Manifesta ciência da operação para NFe
     *
     * @param string $chave Chave da NFe
     * @param string $manifestacao Tipo de manifestação (210200, 210210, 210220, 210240)
     * @param string|null $justificativa Justificativa (obrigatória para Operação não Realizada)
     * @return array
     */
    public function manifestarNFe(string $chave, string $manifestacao, ?string $justificativa = null): array
    {
        try {
            $response = $this->nfeTools->sefazManifesta(
                $chave,
                $manifestacao,
                1, // Sequencial do evento
                $justificativa
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
     * Manifesta ciência da operação para CTe
     *
     * @param string $chave Chave do CTe
     * @param string $manifestacao Tipo de manifestação (210200, 210210, 210220, 210240)
     * @param string|null $justificativa Justificativa (obrigatória para Operação não Realizada)
     * @return array
     */
    public function manifestarCTe(string $chave, string $manifestacao, ?string $justificativa = null): array
    {
        try {
            $response = $this->cteTools->sefazManifesta(
                $chave,
                $manifestacao,
                1, // Sequencial do evento
                $justificativa
            );

            // Padroniza a resposta
            $st = new CTeStandardize();
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
    public function downloadXmlNFe(string $chave): array
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

    /**
     * Download do XML do CTe
     *
     * @param string $chave Chave do CTe
     * @return array
     */
    public function downloadXmlCTe(string $chave): array
    {
        try {
            $response = $this->cteTools->sefazDownload($chave);

            // Padroniza a resposta
            $st = new CTeStandardize();
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
     * Retorna os tipos de manifestação disponíveis
     *
     * @return array
     */
    public static function getTiposManifestacao(): array
    {
        return [
            '210200' => 'Confirmação da Operação',
            '210210' => 'Ciência da Operação',
            '210220' => 'Desconhecimento da Operação',
            '210240' => 'Operação não Realizada'
        ];
    }
}
