<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use App\Models\Tenant\Organization;
use App\Jobs\Tenant\UpdateAnalyticsCache;
use App\Jobs\Tenant\CreateOrganizationAndUserForTenant;
use App\Jobs\Tenant\CreateFrameworkDirectoriesForTenant;

class SetupNewTenant extends Command
{
    /**
     * O nome e a assinatura do comando.
     *
     * @var string
     */
    protected $signature = 'admin:setup-new-tenant {--tenant_id= : ID do tenant específico para atualizar (opcional)}';

    /**
     * A descrição do comando.
     *
     * @var string
     */
    protected $description = 'Prepara novo tenant após o banco de dados ser criado';

    /**
     * Execute o comando.
     */
    public function handle()
    {
        $tenantId = $this->option('tenant_id');


        if ($tenantId) {
            // Atualizar apenas um tenant específico
            $tenant = Tenant::find($tenantId);

            if (!$tenant) {
                $this->error("Tenant com ID {$tenantId} não encontrado.");
                return Command::FAILURE;
            }

            CreateFrameworkDirectoriesForTenant::dispatch($tenant);
            CreateOrganizationAndUserForTenant::dispatch($tenant);
            
            $this->info("Preparação do tenant {$tenant->razao_social} concluída.");

            return Command::SUCCESS;
        }

        
    }

    
}
