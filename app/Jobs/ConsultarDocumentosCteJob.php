<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant\ConsultaCteLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Fiscal\AutoConsultaCteService;

class ConsultarDocumentosCteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de tentativas do job
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Timeout do job em segundos
     *
     * @var int
     */
    public $timeout = 3200;

    /**
     * ID da organização que será consultada
     *
     * @var string
     */
    protected $organizationId;

    /**
     * Cria uma nova instância do job
     *
     * @param string $organizationId
     * @return void
     */
    public function __construct(string $organizationId)
    {
        $this->organizationId = $organizationId;

        // Garante que cada organização tenha seu próprio job
        $this->onQueue('consulta-cte-' . $organizationId);
    }

    /**
     * Executa o job
     *
     * @return void
     */
    public function handle()
    {
        // Usa um lock para evitar execuções concorrentes para a mesma organização
        $lockKey = 'consulta_cte_lock_' . $this->organizationId;

        if (Cache::has($lockKey)) {
            Log::info('Consulta CTe já em andamento para a organização', [
                'organization_id' => $this->organizationId
            ]);
            return;
        }

        // Configura o lock por 10 minutos (tempo máximo esperado para uma consulta)
        Cache::put($lockKey, true, 600);

        try {
            $organization = Organization::findOrFail($this->organizationId);

            // Verifica se a organização ainda é elegível
            if (
                $organization->is_enable_cte_servico &&
                $organization->validade_certificado > now()
            ) {

                Log::info('Organização não está mais elegível para consulta automática de CTe', [
                    'organization_id' => $this->organizationId
                ]);

                Cache::forget($lockKey);
                return;
            }

            $service = new AutoConsultaCteService();
            $resultado = $service->consultarDocumentosCte($organization);

            // Registra o resultado da consulta
            $this->registrarResultadoConsulta($organization, $resultado);

            Log::info('Consulta de documentos CTe finalizada', [
                'organization_id' => $this->organizationId,
                'success' => $resultado['success']
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao processar consulta de documentos CTe', [
                'organization_id' => $this->organizationId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Se for uma falha que mereça nova tentativa, lança a exceção
            if ($this->attempts() < $this->tries) {
                Cache::forget($lockKey);
                throw $e;
            }
        } finally {
            // Sempre libera o lock quando terminar
            Cache::forget($lockKey);
        }
    }

    /**
     * Registra o resultado da consulta no banco de dados
     *
     * @param Organization $organization
     * @param array $resultado
     * @return void
     */
    private function registrarResultadoConsulta(Organization $organization, array $resultado)
    {
        DB::transaction(function () use ($organization, $resultado) {
            // Atualiza o último horário de consulta na organização
            $organization->ultima_consulta_cte = now();
            $organization->save();

            // Registra o log de consulta
            ConsultaCteLog::create([
                'organization_id' => $organization->id,
                'sucesso' => $resultado['success'],
                'mensagem' => $resultado['mensagem'],
                'detalhes' => json_encode($resultado['detalhes']),
                'created_at' => now()
            ]);
        });
    }

    /**
     * Determina o que acontece em caso de falha do job
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        Log::error('Falha definitiva na consulta de documentos CTe', [
            'organization_id' => $this->organizationId,
            'exception' => $exception->getMessage()
        ]);

        // Libera o lock em caso de falha
        Cache::forget('consulta_cte_lock_' . $this->organizationId);
    }
}
