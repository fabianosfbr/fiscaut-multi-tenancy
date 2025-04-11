<?php

namespace App\Services\Fiscal;

use Exception;
use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Log;

class SefazConnectionService
{
    private Organization $organization;
    private SefazNfeService $nfeService;
    private SefazCteService $cteService;
    private string $ambiente;

    public function __construct(Organization $organization, string $ambiente = 'producao')
    {
        $this->organization = $organization;
        $this->ambiente = $ambiente;
        $this->nfeService = new SefazNfeService($organization, $ambiente);
        $this->cteService = new SefazCteService($organization, $ambiente);
    }

    /**
     * Retorna a instância do serviço para NFe
     */
    public function getNfeService(): SefazNfeService
    {
        return $this->nfeService;
    }

    /**
     * Retorna a instância do serviço para CTe
     */
    public function getCteService(): SefazCteService
    {
        return $this->cteService;
    }

    /**
     * Retorna a instância configurada do Tools para NFe
     */
    public function getNFeTools()
    {
        return $this->nfeService->getTools();
    }

    /**
     * Retorna a instância configurada do Tools para CTe
     */
    public function getCTeTools()
    {
        return $this->cteService->getTools();
    }

    /**
     * Consulta documentos NFe destinados à organização
     *
     * @param int|null $nsuEspecifico NSU específico para consulta
     * @return array Resposta da SEFAZ
     */
    public function consultarNFeDestinadas(?int $nsuEspecifico = null): array
    {
        return $this->nfeService->consultarDocumentosDestinados($nsuEspecifico);
    }

    /**
     * Consulta documentos CTe destinados à organização
     *
     * @param int|null $nsuEspecifico NSU específico para consulta
     * @return array Resposta da SEFAZ
     */
    public function consultarCTeDestinados(?int $nsuEspecifico = null): array
    {
        return $this->cteService->consultarDocumentosDestinados($nsuEspecifico);
    }

    /**
     * Verifica e processa NSUs faltantes
     */
    public function verificarNsusFaltantes($tipoDocumento): array
    {
        if ($tipoDocumento === 'NFe') {
            return $this->nfeService->verificarNsusFaltantes();
        } elseif ($tipoDocumento === 'CTe') {
            return $this->cteService->verificarNsusFaltantes();
        } else {
            return [
                'success' => false,
                'message' => 'Tipo de documento inválido'
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
        return $this->nfeService->manifestar($chave, $manifestacao, $justificativa);
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
        return $this->cteService->manifestar($chave, $manifestacao, $justificativa);
    }

    /**
     * Download do XML da NFe
     *
     * @param string $chave Chave da NFe
     * @return array
     */
    public function downloadXmlNFe(string $chave): array
    {
        return $this->nfeService->downloadXml($chave);
    }

    /**
     * Download do XML do CTe
     *
     * @param string $chave Chave do CTe
     * @return array
     */
    public function downloadXmlCTe(string $chave): array
    {
        return $this->cteService->downloadXml($chave);
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
