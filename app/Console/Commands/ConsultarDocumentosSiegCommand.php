<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant\SiegImportacao;
use App\Jobs\Sieg\ProcessarImportacaoSiegJob;

class ConsultarDocumentosSiegCommand extends Command
{
    /**
     * O nome e a assinatura do comando
     *
     * @var string
     */
    protected $signature = 'fiscal:consultar-documentos-sieg 
                           {--tenant_id= : ID da tenant} 
                           {--organization_cnpj= : CNPJ específico de uma organização}';

    /**
     * A descrição do comando
     *
     * @var string
     */
    protected $description = 'Agenda a consulta de documentos fiscais para organizações elegíveis';

    /**
     * Mapeamento de tipos de documentos
     * 
     * @var array
     */
    protected const TIPOS_DOCUMENTOS = [
        'NFe' => 1,
        'CT-e' => 2,
        'NFSe' => 3,
        'NFCe' => 4,
        'CF-e' => 5
    ];

    /**
     * Executa o comando
     *
     * @return int
     */
    public function handle()
    {
        $tenantId = $this->option('tenant_id');
        $organizationCnpj = $this->option('organization_cnpj');

        $this->info('Iniciando agendamento de consultas de documentos fiscais no SIEG...');

        try {
            if ($tenantId) {
                // Processa apenas o tenant específico
                tenancy()->initialize($tenantId);
                $this->info("Executando para o tenant: " . tenant()->id);
                $this->processarTenant($organizationCnpj);
            } else {
                // Processa todos os tenants
                $tenants = Tenant::all();

                if ($tenants->isEmpty()) {
                    $this->error('Nenhum tenant encontrado');
                    return 1;
                }

                $this->info("Processando para {$tenants->count()} tenants...");

                foreach ($tenants as $tenant) {
                    $this->info("Processando tenant: {$tenant->id} - {$tenant->razao_social}");
                    tenancy()->initialize($tenant->id);
                    $this->processarTenant($organizationCnpj);
                    tenancy()->end();
                }
            }

            $this->info("Processamento concluído com sucesso");
            return 0;
        } catch (\Exception $e) {
            $this->error("Erro ao processar importações: {$e->getMessage()}");
            Log::error('Erro no comando fiscal:consultar-documentos-sieg', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        } finally {
            // Finaliza o contexto do tenant atual
            tenancy()->end();
        }
    }

    /**
     * Processa um tenant específico
     * 
     * @param string|null $organizationCnpj CNPJ específico de uma organização
     * @return void
     */
    private function processarTenant(?string $organizationCnpj): void
    {
        if ($organizationCnpj) {
            // Processa apenas a organização específica
            $organization = Organization::whereCnpj($organizationCnpj)->first();

            if (!$organization) {
                $this->error("Organização com CNPJ {$organizationCnpj} não encontrada");
                return;
            }

            $this->processarImportacao($organization);
        } else {
            // Processa todas as organizações elegíveis
            $organizacoes = $this->getOrganizacoesElegiveis();

            if ($organizacoes->isEmpty()) {
                $this->info("Nenhuma organização elegível para sincronização SIEG encontrada");
                return;
            }

            $this->withProgressBar($organizacoes, function ($organizacao) {
                $this->processarImportacao($organizacao);
            });

            $this->newLine();
        }
    }

    /**
     * Obtém as organizações elegíveis para importação SIEG
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getOrganizacoesElegiveis()
    {
        return Organization::query()
            ->where('is_enable_sync_sieg', true)
            ->get();
    }

    /**
     * Processa a importação para uma organização
     * 
     * @param Organization $organizacao
     * @return void
     */
    private function processarImportacao(Organization $organizacao)
    {        
        $superAdmin = $this->getSuperAdmin($organizacao);

        if (!$superAdmin || !$superAdmin->sieg()->first()?->sieg_api_key) {
            $this->info("Organização {$organizacao->razao_social} não possui super admin ou não possui chave de API SIEG.");
            return;
        }

        // Obtém o período para consulta
        [$dataInicial, $dataFinal] = $this->obterPeriodoConsulta($organizacao);

        // Processa documentos NFe
        // $this->processarConsultaNfe($organizacao, $superAdmin, $dataInicial, $dataFinal);

        // Processa eventos NFe
        $this->processarConsultaEventosNfe($organizacao, $superAdmin, $dataInicial, $dataFinal);
    }

    /**
     * Obtém o super admin de uma organização
     * 
     * @param Organization $organizacao
     * @return \App\Models\Tenant\User|null
     */
    private function getSuperAdmin(Organization $organizacao)
    {
        return $organizacao->users()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'super-admin');
            })
            ->first();
    }

    /**
     * Obtém o período de consulta para a organização
     * 
     * @param Organization $organizacao
     * @return array [dataInicial, dataFinal]
     */
    private function obterPeriodoConsulta(Organization $organizacao): array
    {
        $ultimaImportacaoNfe = SiegImportacao::where('tipo_documento', 'NFe')
            ->where('organization_id', $organizacao->id)
            ->where('tipo_cnpj', 'emitente')
            ->orderBy('data_final', 'desc')
            ->first();

        if ($ultimaImportacaoNfe) {
            // Caso tenha última importação, consulta a partir da data final - 2 dias
            $dataFinal = Carbon::parse($ultimaImportacaoNfe->data_final)->format('Y-m-d');
            $dataInicial = Carbon::parse($dataFinal)->subDays(2)->format('Y-m-d');
        } else {
            // Caso não tenha importação, consulta os últimos 2 dias
            $dataFinal = Carbon::now()->format('Y-m-d');
            $dataInicial = Carbon::parse($dataFinal)->subDays(2)->format('Y-m-d');
        }

        return [$dataInicial, $dataFinal];
    }

    /**
     * Processa a consulta de NFe para uma organização
     * 
     * @param Organization $organizacao
     * @param \App\Models\Tenant\User $superAdmin
     * @param string $dataInicial
     * @param string $dataFinal
     * @return void
     */
    private function processarConsultaNfe(Organization $organizacao, $superAdmin, string $dataInicial, string $dataFinal): void
    {
        // Consulta NFe emitente (tipo 1 = NFe)
        $idRegistro = $this->gerarRegistroImportacao(
            $organizacao,
            $superAdmin,
            $dataInicial,
            $dataFinal,
            'NFe',
            'emitente',
            false
        );

        $this->despacharJob(
            $organizacao,
            $dataInicial,
            $dataFinal,
            self::TIPOS_DOCUMENTOS['NFe'],
            'emitente',
            false,
            $superAdmin->id,
            $idRegistro
        );
    }

    /**
     * Processa a consulta de eventos de NFe para uma organização
     * 
     * @param Organization $organizacao
     * @param \App\Models\Tenant\User $superAdmin
     * @param string $dataInicial
     * @param string $dataFinal
     * @return void
     */
    private function processarConsultaEventosNfe(Organization $organizacao, $superAdmin, string $dataInicial, string $dataFinal): void
    {
        // Consulta eventos de NFe (tipo 1 = NFe)
        $idRegistro = $this->gerarRegistroImportacao(
            $organizacao,
            $superAdmin,
            $dataInicial,
            $dataFinal,
            'NFe',
            'emitente',
            true
        );

        $this->despacharJob(
            $organizacao,
            $dataInicial,
            $dataFinal,
            self::TIPOS_DOCUMENTOS['NFe'],
            'emitente',
            true,
            $superAdmin->id,
            $idRegistro
        );
    }

    /**
     * Despacha o job de processamento
     * 
     * @param Organization $organizacao
     * @param string $dataInicial
     * @param string $dataFinal
     * @param int $tipoDocumento
     * @param string $tipoCnpj
     * @param bool $downloadEventos
     * @param int $userId
     * @param int $registroId
     * @return void
     */
    private function despacharJob(
        Organization $organizacao,
        string $dataInicial,
        string $dataFinal,
        int $tipoDocumento,
        string $tipoCnpj,
        bool $downloadEventos,
        string $userId,
        int $registroId
    ): void {
        ProcessarImportacaoSiegJob::dispatch(
            $organizacao,
            $dataInicial,
            $dataFinal,
            $tipoDocumento,
            $tipoCnpj,
            $downloadEventos,
            $userId,
            $registroId
        );
    }

    /**
     * Gera um registro de importação na tabela sieg_importacoes
     * 
     * @param Organization $organizacao
     * @param \App\Models\Tenant\User $user
     * @param string $dataInicial
     * @param string $dataFinal
     * @param string $tipoDesc
     * @param string $tipoCnpj
     * @param bool $downloadEventos
     * @return int ID do registro criado
     */
    private function gerarRegistroImportacao(
        Organization $organizacao,
        $user,
        string $dataInicial,
        string $dataFinal,
        string $tipoDesc,
        string $tipoCnpj,
        bool $downloadEventos
    ): int {
        $idRegistro = DB::table('sieg_importacoes')->insertGetId([
            'organization_id' => $organizacao->id,
            'user_id' => $user->id,
            'data_inicial' => $dataInicial,
            'data_final' => $dataFinal,
            'tipo_documento' => $tipoDesc,
            'tipo_cnpj' => $tipoCnpj,
            'documentos_processados' => 0,
            'eventos_processados' => 0,
            'total_processados' => 0,
            'total_documentos' => 0,
            'sucesso' => false,
            'status' => 'processando',
            'mensagem' => 'Importação em processamento',
            'download_eventos' => $downloadEventos,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Registro de importação SIEG criado via comando', [
            'id' => $idRegistro,
            'organization_id' => $organizacao->id,
            'tipo_documento' => $tipoDesc,
            'data_inicial' => $dataInicial,
            'data_final' => $dataFinal
        ]);

        return $idRegistro;
    }
}
