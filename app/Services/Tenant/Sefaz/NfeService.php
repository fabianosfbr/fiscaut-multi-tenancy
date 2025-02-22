<?php

namespace App\Services\Tenant\Sefaz;

use App\Jobs\Sefaz\Process\ProcessResponseNfeSefazJob;
use App\Models\Tenant\Organization;
use App\Services\Tenant\Sefaz\Traits\HasLogSefaz;
use App\Services\Tenant\Sefaz\Traits\HasNfe;
use App\Traits\HasXmlReader;
use Exception;
use Illuminate\Support\Facades\Log;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Tools;

class NfeService
{
    use HasLogSefaz, HasNfe, HasXmlReader;

    private Tools $tools;

    private Organization $organization;

    public function issuer($organization)
    {
        $this->organization = $organization;

        return $this;
    }

    private function sefaz()
    {
        $config = [
            'atualizacao' => date('Y-m-d h:i:s'),
            'tpAmb' => config('admin.environment.HAMBIENTE_SEFAZ'),
            'razaosocial' => explode(':', $this->organization->razao_social)[0],
            'siglaUF' => 'SP',
            'cnpj' => $this->organization->cnpj,
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

        $certificado = Certificate::readPfx($this->organization->digitalCertificate->content_file, $this->organization->digitalCertificate->password);

        $this->tools = new Tools(json_encode($config), $certificado);

        $this->tools->model('55');
        // este serviço somente opera em ambiente de produção 1 - produção 2-homoloação
        $this->tools->setEnvironment(config('admin.environment.HAMBIENTE_SEFAZ'));
    }

    public function buscarDocumentosFiscais()
    {

        $this->sefaz();
    }

    public function buscarDocumentosFiscaisPorNsu($nsu, $origem = 'SEFAZ')
    {

        $this->sefaz();

        try {
            // executa a busca pelos documentos
            $response = $this->tools->sefazDistDFe(0, intval($nsu));

            Log::info('Log de consulta NFe - SEFAZ - registro - '.explode(':', $this->organization->razao_social)[0].' : '.$response);
        } catch (Exception $e) {
            Log::error('Log de consulta a SEFAZ NFe - retorno com problema - '.$e->getMessage().' Empresa: '.explode(':', $this->organization->razao_social)[0]);

            return;
        }

        $reader = loadXmlReader($response);

        if ($this->checkEmptyOrError($reader)) {
            return;
        }

        // Processa o lote
        ProcessResponseNfeSefazJob::dispatch($this->organization, $response, $origem)->onQueue('high');
    }
}
