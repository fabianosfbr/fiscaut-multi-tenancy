<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Log;
use App\Jobs\ConsultarDocumentosFiscaisJob;
use App\Services\Fiscal\AutoConsultaNfeService;

class ConsultarDocumentosFiscaisCommand extends Command
{
    /**
     * O nome e a assinatura do comando
     *
     * @var string
     */
    protected $signature = 'fiscal:consultar-documentos {--organization_cnpj= : CNPJ específico de uma organização}';

    /**
     * A descrição do comando
     *
     * @var string
     */
    protected $description = 'Agenda a consulta de documentos fiscais para organizações elegíveis';

    /**
     * Executa o comando
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando agendamento de consultas de documentos fiscais...');

        $service = new AutoConsultaNfeService();

        // Se for especificada uma organização, consulta apenas ela
        if ($organization_cnpj = $this->option('organization_cnpj')) {
            $organization = Organization::whereCnpj($organization_cnpj);

            if (!$organization) {
                $this->error("Organização com CNPJ {$organization_cnpj} não encontrada.");
                return 1;
            }

            $this->info("Agendando consulta para organização {$organization->razao_social}...");

            ConsultarDocumentosFiscaisJob::dispatch($organization->id)
                ->onQueue('consulta-nfe');

            $this->info('Consulta agendada com sucesso!');
            return 0;
        }

        // Caso contrário, agenda para todas as organizações elegíveis
        $resultado = $service->agendarConsultasParaOrganizacoesElegiveis();

        $this->info("Total de organizações elegíveis: {$resultado['total']}");
        $this->info("Consultas agendadas: {$resultado['agendadas']}");

        if ($resultado['falhas'] > 0) {
            $this->warn("Falhas no agendamento: {$resultado['falhas']}");
        }

        return 0;
    }
}
