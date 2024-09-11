<?php

namespace App\Traits;

use DOMDocument;

trait HasXmlReader
{
    public function checkKeyExiste(string $key, array $element)
    {

        return strpos(json_encode($element), $key) > 0 ? true : false;
    }

    public function extractDocs($response)
    {

        $dom = new DOMDocument();
        $dom->loadXML($response);
        $node = $dom->getElementsByTagName('retDistDFeInt')->item(0);
        $lote = $node->getElementsByTagName('loteDistDFeInt')->item(0);

        $docs = $lote->getElementsByTagName('docZip');

        return $docs;
    }
}
