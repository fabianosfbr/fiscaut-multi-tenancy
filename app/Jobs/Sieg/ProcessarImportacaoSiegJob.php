<?php

namespace App\Jobs\Sieg;

use Exception;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\Fiscal\SiegConnectionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class ProcessarImportacaoSiegJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * O número de vezes que o job pode ser tentado.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * O número de segundos que o job pode ser processado antes de ser considerado travado.
     *
     * @var int
     */
    public $timeout = 3600; // 1 hora

    /**
     * Indica se o job deve ser marcado como falha na primeira exceção.
     *
     * @var bool
     */
    public $failOnTimeout = true;

    /**
     * Cria uma nova instância do job.
     */
    public function __construct(
        private readonly Organization $organization,
        private readonly string $dataInicial,
        private readonly string $dataFinal,
        private readonly int $tipoDocumento,
        private readonly string $tipoCnpj,
        private readonly bool $downloadEventos,
        private readonly ?string $userId = null,
        private ?int $jobId = null
    ) {}

    /**
     * Executa o job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        Log::info("Iniciando processamento de importação SIEG", [
            'organization_id' => $this->organization->id,
            'user_id' => $this->userId,
            'tipo_documento' => $this->tipoDocumento,
            'data_inicial' => $this->dataInicial,
            'data_final' => $this->dataFinal,
            'download_eventos' => $this->downloadEventos
        ]);

        try {
            // Inicializa o registro da importação no banco
            $jobId = $this->iniciarRegistroImportacao();
            $this->jobId = $jobId;

            // Inicializa serviço
            $siegService = new SiegConnectionService($this->organization);

            // Define a função de callback para atualização de progresso
            $progressCallback = function ($skip, $totalDocs = null) {
                $this->atualizarProgresso($skip, $totalDocs);
            };

            // Executa a busca de acordo com o tipo de documento
            $resultado = $this->executarConsultaPorTipo($siegService, $progressCallback);

            // Finaliza o registro da importação
            $this->finalizarRegistroImportacao($resultado);

            $timeElapsed = round(microtime(true) - $startTime, 2);
            Log::info("Importação SIEG concluída com sucesso", [
                'organization_id' => $this->organization->id,
                'job_id' => $this->jobId,
                'documentos_processados' => $resultado['documentos_processados'] ?? 0,
                'eventos_processados' => $resultado['eventos_processados'] ?? 0,
                'tempo_execucao' => $timeElapsed . 's'
            ]);
        } catch (Exception $e) {
            Log::error("Erro no processamento da importação SIEG", [
                'organization_id' => $this->organization->id,
                'job_id' => $this->jobId,
                'erro' => $e->getMessage(),
                'linha' => $e->getLine(),
                'arquivo' => $e->getFile()
            ]);

            // Em caso de erro, finaliza o registro com status de falha
            if ($this->jobId) {
                $this->finalizarRegistroImportacao([
                    'success' => false,
                    'message' => "Erro: " . $e->getMessage(),
                    'documentos_processados' => 0,
                    'eventos_processados' => 0,
                    'total_documentos' => 0
                ]);
            }

            throw $e; // Relança a exceção para que o job seja marcado como falha
        }
    }

    /**
     * Executa a consulta de acordo com o tipo de documento.
     */
    private function executarConsultaPorTipo(
        SiegConnectionService $siegService, 
        callable $progressCallback
    ): array {
        return match ($this->tipoDocumento) {
            SiegConnectionService::XML_TYPE_NFE => $siegService->baixarTodosXmlsNFePorPeriodo(
                $this->dataInicial,
                $this->dataFinal,
                $this->tipoCnpj,
                $this->downloadEventos,
                $progressCallback
            ),
            SiegConnectionService::XML_TYPE_CTE => $siegService->baixarTodosCTePorPeriodo(
                $this->dataInicial,
                $this->dataFinal,
                $this->tipoCnpj,
                $this->downloadEventos,
                $progressCallback
            ),
            default => $siegService->baixarTodosDocumentosPorTipo(
                $this->dataInicial,
                $this->dataFinal,
                $this->tipoDocumento,
                $this->tipoCnpj,
                $this->downloadEventos,
                $progressCallback
            ),
        };
    }

    /**
     * Inicia o registro da importação no banco.
     */
    private function iniciarRegistroImportacao(): int
    {
        $tiposDocumento = SiegConnectionService::getTiposDocumento();
        $tipoDesc = $tiposDocumento[$this->tipoDocumento] ?? "Tipo {$this->tipoDocumento}";
        
        return DB::table('sieg_importacoes')->insertGetId([
            'organization_id' => $this->organization->id,
            'user_id' => $this->userId,
            'data_inicial' => $this->dataInicial,
            'data_final' => $this->dataFinal,
            'tipo_documento' => $tipoDesc,
            'tipo_cnpj' => $this->tipoCnpj,
            'documentos_processados' => 0,
            'eventos_processados' => 0,
            'total_processados' => 0,
            'total_documentos' => 0,
            'sucesso' => false,
            'mensagem' => 'Importação em andamento...',
            'download_eventos' => $this->downloadEventos,
            'status' => 'processando',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Atualiza o progresso da importação.
     */
    private function atualizarProgresso(int $skip, ?int $totalDocs = null, bool $finished = false): void
    {
        if (!$this->jobId) {
            return;
        }

        // Status a ser definido
        $status = $finished ? 'concluido' : 'processando';
        
        // Mensagem a ser exibida
        $progressoMsg = $finished 
            ? "Importação concluída com sucesso. Documentos encontrados: {$totalDocs}"
            : "Processando página {$skip}. " . ($totalDocs ? "Total de documentos: {$totalDocs}" : "");

        // Atualiza a tabela de importações
        DB::table('sieg_importacoes')
            ->where('id', $this->jobId)
            ->update([
                'mensagem' => $progressoMsg,
                'status' => $status,
                'sucesso' => true,
                'total_documentos' => $totalDocs ?? 0,
                'updated_at' => now(),
            ]);
        
        Log::info("Atualização de progresso da importação SIEG", [
            'job_id' => $this->jobId,
            'skip' => $skip,
            'status' => $status,
            'finished' => $finished,
            'total_docs' => $totalDocs
        ]);
    }

    /**
     * Finaliza o registro da importação.
     */
    private function finalizarRegistroImportacao(array $resultado): void
    {
        if (!$this->jobId) {
            return;
        }

        $totalProcessados = ($resultado['documentos_processados'] ?? 0) + ($resultado['eventos_processados'] ?? 0);
        
        // Define o status como concluído mesmo se não houver documentos, desde que a consulta tenha sido bem-sucedida
        $status = $resultado['success'] ?? false ? 'concluido' : 'erro';
        
        // Define uma mensagem apropriada para o caso de sucesso sem documentos
        $mensagem = $resultado['message'] ?? null;
        if ($resultado['success'] && $totalProcessados == 0) {
            $mensagem = 'Consulta concluída com sucesso. Nenhum documento encontrado para os parâmetros informados.';
        } elseif ($resultado['success']) {
            $mensagem = "Importação concluída com sucesso. Foram processados {$totalProcessados} documentos.";
        }

        DB::table('sieg_importacoes')
            ->where('id', $this->jobId)
            ->update([
                'documentos_processados' => $resultado['documentos_processados'] ?? 0,
                'eventos_processados' => $resultado['eventos_processados'] ?? 0,
                'total_processados' => $totalProcessados,
                'total_documentos' => $resultado['total_documentos'] ?? 0,
                'sucesso' => $resultado['success'] ?? false,
                'mensagem' => $mensagem,
                'status' => $status,
                'updated_at' => now(),
            ]);
    }
} 