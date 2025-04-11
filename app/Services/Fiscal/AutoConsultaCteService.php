<?php

namespace App\Services\Fiscal;

use Exception;
use Carbon\Carbon;
use App\Models\Tenant\Organization;
use App\Jobs\ConsultarDocumentosCteJob;
use Illuminate\Support\Facades\Log;

class AutoConsultaCteService
{
    /**
     * Inicia o processo de consulta automática de documentos fiscais CTe
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
                ConsultarDocumentosCteJob::dispatch($organization->id)
                    ->onQueue('consulta-cte');
                
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
                
                Log::error('Falha ao agendar consulta de documentos CTe', [
                    'organization_id' => $organization->id,
                    'erro' => $e->getMessage()
                ]);
            }
        });

        return $resultados;
    }

    /**
     * Obtém as organizações elegíveis para consulta automática de CTe
     * (com certificado válido e serviço de CTe habilitado)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOrganizacoesElegiveis()
    {
        return Organization::query()
            ->where('is_enable_cte_servico', true)
            ->where('validade_certificado', '>', Carbon::now())        
            ->get();
    }

    /**
     * Consulta documentos CTe para uma organização específica
     * 
     * @param Organization $organization
     * @return array Resultados da consulta
     */
    public function consultarDocumentosCte(Organization $organization): array
    {
        $sefazService = new SefazConnectionService($organization);
        
        try {
            $resultado = $sefazService->consultarCTeDestinados();
            
            return [
                'success' => $resultado['success'],
                'mensagem' => $resultado['success'] ? 'Consulta CTe realizada com sucesso' : $resultado['message'],
                'detalhes' => $resultado['success'] ? $resultado : null,
                'organization_id' => $organization->id
            ];
        } catch (Exception $e) {
            Log::error('Erro ao consultar documentos CTe', [
                'organization_id' => $organization->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'mensagem' => 'Erro ao consultar documentos CTe: ' . $e->getMessage(),
                'detalhes' => null,
                'organization_id' => $organization->id
            ];
        }
    }
} 