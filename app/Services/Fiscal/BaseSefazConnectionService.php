<?php

namespace App\Services\Fiscal;

use Exception;
use DOMDocument;
use Carbon\Carbon;
use NFePHP\Common\Certificate;
use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessarDocumentoFiscal;

abstract class BaseSefazConnectionService
{
    protected Organization $organization;
    protected string $ambiente;
    protected string $tipoDocumento;

    public function __construct(Organization $organization, string $ambiente = 'producao')
    {
        $this->organization = $organization;
        $this->ambiente = $ambiente;
        $this->configurar();
    }

    /**
     * Configura a conexão com a SEFAZ usando os dados da organização
     */
    abstract protected function configurar(): void;

    /**
     * Processa a resposta da SEFAZ e os documentos do lote
     * 
     * @param string $response Resposta XML da SEFAZ
     * @return array Resultado processado
     */
    protected function processarRespostaSefaz(string $response): array
    {
        $dom = new DOMDocument();
        $dom->loadXML($response);

        // Detecta automaticamente se é uma resposta de NFe ou CTe
        $node = $dom->getElementsByTagName('retDistDFeInt')->item(0);

        if (!$node) {
            return [
                'success' => false,
                'message' => 'Formato de resposta não reconhecido',
                'documentos_enfileirados' => 0
            ];
        }

        // Extrai informações do retorno
        $cStat = $node->getElementsByTagName('cStat')->item(0)?->nodeValue;
        $xMotivo = $node->getElementsByTagName('xMotivo')->item(0)?->nodeValue;
        $ultNSU = $node->getElementsByTagName('ultNSU')->item(0)?->nodeValue;
        $maxNSU = $node->getElementsByTagName('maxNSU')->item(0)?->nodeValue;
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

        $documentosEnfileirados = 0;

        if (!empty($lote)) {
            $docs = $lote->getElementsByTagName('docZip');

            foreach ($docs as $doc) {
                $schema = $doc->getAttribute('schema');
                $content = gzdecode(base64_decode($doc->nodeValue));

                // Determina o tipo de documento com base no schema
                $tipoDocumento = $this->detectarTipoDocumento($schema);

                // Enfileira o processamento do documento
                ProcessarDocumentoFiscal::dispatch(
                    $this->organization,
                    $content,
                    $schema,
                    $tipoDocumento
                );

                $documentosEnfileirados++;
            }
        }

        return [
            'success' => true,
            'ultimo_nsu' => $ultNSU,
            'max_nsu' => $maxNSU,
            'status' => $cStat,
            'motivo' => $xMotivo,
            'xml_content' => $response,
            'documentos_enfileirados' => $documentosEnfileirados
        ];
    }

    /**
     * Detecta o tipo de documento com base no schema
     * 
     * @param string $schema Schema do documento
     * @return string Tipo do documento ('NFe' ou 'CTe')
     */
    protected function detectarTipoDocumento(string $schema): string
    {
        if (
            strpos($schema, 'procCTe') !== false ||
            strpos($schema, 'cteProc') !== false ||
            strpos($schema, 'procEventoCTe') !== false
        ) {
            return 'CTe';
        }

        return 'NFe'; // Por padrão assume NFe
    }

    /**
     * Consulta um NSU específico
     */
    abstract public function consultarNsuEspecifico(int $nsu): array;

    /**
     * Consulta documentos destinados à organização
     */
    abstract public function consultarDocumentosDestinados(?int $nsuEspecifico = null): array;

    /**
     * Consulta todos os documentos a partir do último NSU
     */
    abstract protected function consultarTodosDocumentos(): array;

    /**
     * Download do XML do documento
     */
    abstract public function downloadXml(string $chave): array;

    /**
     * Manifesta ciência da operação
     */
    abstract public function manifestar(string $chave, string $manifestacao, ?string $justificativa = null): array;

    /**
     * Verifica e processa NSUs faltantes
     */
    abstract public function verificarNsusFaltantes(): array;
} 