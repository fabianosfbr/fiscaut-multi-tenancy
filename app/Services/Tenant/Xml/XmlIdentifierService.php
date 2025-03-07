<?php

namespace App\Services\Tenant\Xml;

use SimpleXMLElement;
use Exception;

class XmlIdentifierService
{
    public const TIPO_NFE = 'NFE';
    public const TIPO_CTE = 'CTE';

    public static function identificarTipoXml(string $xmlContent): string
    {
        try {
            $xml = new SimpleXMLElement($xmlContent);

            if (isset($xml->NFe)) {
                return self::TIPO_NFE;
            }

            if (isset($xml->CTe)) {
                return self::TIPO_CTE;
            }

            throw new Exception('XML não identificado como NFe ou CTe');
        } catch (Exception $e) {
            throw new Exception('Erro ao identificar tipo do XML: ' . $e->getMessage());
        }
    }
} 