<?php

namespace App\Services\Fiscal;

use Exception;
use Carbon\Carbon;
use App\Models\Tenant\Organization;
use App\Jobs\ConsultarDocumentosFiscaisJob;
use Illuminate\Support\Facades\Log;

class AutoConsultaNfeService
{
    /**
     * Inicia o processo de consulta automática de documentos fiscais
     * para todas as organizações elegíveis
     *
     * @return array Resultados do agendamento
     */
    public function agendarConsultasParaOrganizacoesElegiveis(): array
    {
        $organizacoes = $this->getOrganizacoesElegiveis();
        $resultados = [
            'total' => $organizacoes->count(),
            'agendadas' => 0,
            'falhas' => 0,
            'detalhes' => []
        ];

        $organizacoes->each(function (Organization $organization) use (&$resultados) {
            try {
                // Agenda job individualizado para cada organização
                ConsultarDocumentosFiscaisJob::dispatch($organization->id)
                    ->onQueue('consulta-nfe');
                
                $resultados['agendadas']++;
                $resultados['detalhes'][] = [
                    'organization_id' => $organization->id,
                    'nome' => $organization->name,
                    'status' => 'agendado'
                ];
            } catch (Exception $e) {
                $resultados['falhas']++;
                $resultados['detalhes'][] = [
                    'organization_id' => $organization->id,
                    'nome' => $organization->name,
                    'status' => 'falha',
                    'erro' => $e->getMessage()
                ];
                
                Log::error('Falha ao agendar consulta de documentos fiscais', [
                    'organization_id' => $organization->id,
                    'erro' => $e->getMessage()
                ]);
            }
        });

        return $resultados;
    }

    /**
     * Obtém as organizações elegíveis para consulta automática
     * (com certificado válido e serviço de NFe habilitado)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOrganizacoesElegiveis()
    {
        return Organization::query()
            ->where('is_enable_nfe_servico', true)
            ->where('validade_certificado', '>', Carbon::now())        
            ->get();
    }

    /**
     * Consulta documentos fiscais para uma organização específica
     * 
     * @param Organization $organization
     * @return array Resultados da consulta
     */
    public function consultarDocumentosFiscais(Organization $organization): array
    {
        $sefazService = new SefazConnectionService($organization);
        
        try {
            $resultado = $sefazService->consultarNFeDestinadas();
            
            return [
                'success' => $resultado['success'],
                'mensagem' => $resultado['success'] ? 'Consulta realizada com sucesso' : $resultado['message'],
                'detalhes' => $resultado['success'] ? $resultado : null,
                'organization_id' => $organization->id
            ];
        } catch (Exception $e) {
            Log::error('Erro ao consultar documentos fiscais', [
                'organization_id' => $organization->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'mensagem' => 'Erro ao consultar documentos: ' . $e->getMessage(),
                'detalhes' => null,
                'organization_id' => $organization->id
            ];
        }
    }
} 