<?php

namespace App\Services\Tenant\Integracoes\Dominio\Nfe;

use SimpleXMLElement;
use App\Models\Tenant\ProdutoFornecedor;
use App\Services\Tenant\Integracoes\Dominio\Nfe\Registro20;
use App\Services\Tenant\Integracoes\Dominio\Nfe\Registro30;
use App\Services\Tenant\Integracoes\Dominio\Traits\HelperFunctions;

class LeiautePadrao
{

    public static function generate($records, $currentIssuer)
    {
        $txtContent = '|0000|' . $currentIssuer->cnpj .  "|" . PHP_EOL;


        $emitentes = $records->unique(function ($nfe) {
            return $nfe->cnpj_emitente .  $nfe->ie_emitente;
        });

        //Registro 20
        foreach ($emitentes as $issuer) {

            $isEntradaPropria = $issuer->cnpj_emitente == $currentIssuer->cnpj;

            $cnpj = $isEntradaPropria ? $currentIssuer->cnpj :  $issuer->cnpj_emitente;

            $docs = $records->where('cnpj_emitente', $cnpj);

            //Somente documentos etiquetados
            foreach ($docs as $doc) {
                $xml = new SimpleXMLElement($doc->xml_content);

                $checkRegistro20 = Registro20::processar($xml, $doc, $currentIssuer);

                if (!str_contains($txtContent, $checkRegistro20)) {
                    $txtContent .= $checkRegistro20;
                }
            }
        }


        //Registro 30
        foreach ($emitentes as $issuer) {

            $isEntradaPropria = $issuer->cnpj_emitente == $currentIssuer->cnpj;

            $cnpj = $isEntradaPropria ? $currentIssuer->cnpj :  $issuer->cnpj_emitente;

            $docs = $records->where('cnpj_emitente', $cnpj);

            //Somente documentos etiquetados
            foreach ($docs as $doc) {
                $xml = new SimpleXMLElement($doc->xml_content);

                if (isset($xml->NFe->infNFe->transp->transporta->CNPJ)) {
                    $checkRegistro30 = Registro30::processar($xml);

                    if (!str_contains($txtContent, $checkRegistro30)) {
                        $txtContent .= $checkRegistro30;
                    }
                }
            }
        }



        //Registro 100, 110, 135, 150
        foreach ($emitentes as $issuer) {

            $isEntradaPropria = $issuer->cnpj_emitente == $currentIssuer->cnpj;

            $cnpj = $isEntradaPropria ? $currentIssuer->cnpj :  $issuer->cnpj_emitente;

            $docs = $records->where('cnpj_emitente', $cnpj);

            //Somente documentos etiquetados
            foreach ($docs as $doc) {
                $xml = new SimpleXMLElement($doc->xml_content);

                $produtos = HelperFunctions::processaProduto($xml);

                HelperFunctions::registrarProduto($produtos, $doc);

                //Registro 100
                $checkRegistro100 = Registro100::processar($xml,  $doc, $currentIssuer, $produtos);

                if (!str_contains($txtContent, $checkRegistro100)) {
                    $txtContent .= $checkRegistro100;
                }
            }
        }

         //Registro 1000
         foreach ($emitentes as $issuer) {

            $isEntradaPropria = $issuer->cnpj_emitente == $currentIssuer->cnpj;

            $cnpj = $isEntradaPropria ? $currentIssuer->cnpj :  $issuer->cnpj_emitente;

            $docs = $records->where('cnpj_emitente', $cnpj);

            //Somente documentos etiquetados
            foreach ($docs as $doc) {
                $xml = new SimpleXMLElement($doc->xml_content);

                $checkRegistro1000 = Registro1000::processar($xml, $doc, $currentIssuer);

                if (!str_contains($txtContent, $checkRegistro1000)) {
                    $txtContent .= $checkRegistro1000;
                }
            }

         }

        return $txtContent;
    }
}
