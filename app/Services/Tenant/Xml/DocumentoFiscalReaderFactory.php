<?php

namespace App\Services\Tenant\Xml;

use Exception;
use SimpleXMLElement;
use App\Interfaces\ServicoLeituraDocumentoFiscal;

class DocumentoFiscalReaderFactory
{
    /**
     * Retorna o serviço de leitura apropriado para o conteúdo XML
     *
     * @param string $xmlContent Conteúdo do XML
     * @return ServicoLeituraDocumentoFiscal
     * @throws Exception
     */
    public static function createFromXml(string $xmlContent): ServicoLeituraDocumentoFiscal
    {
        try {
            $xml = new SimpleXMLElement($xmlContent);
            
            // Verificar o tipo de documento
            if (isset($xml->NFe) || isset($xml->nfeProc) || isset($xml->nfe)) {
                return app(XmlNfeReaderService::class);
            }
            
            if (isset($xml->CTe) || isset($xml->cteProc) || isset($xml->cte)) {
                return app(XmlCteReaderService::class);
            }
            
            throw new Exception('Tipo de documento fiscal não suportado');
        } catch (Exception $e) {
            throw new Exception('Erro ao identificar tipo de documento: ' . $e->getMessage());
        }
    }
} 