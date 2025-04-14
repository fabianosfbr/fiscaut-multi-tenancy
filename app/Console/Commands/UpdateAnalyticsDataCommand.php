<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use App\Models\Tenant\Organization;
use App\Jobs\Tenant\UpdateAnalyticsCache;

class UpdateAnalyticsDataCommand extends Command
{
    /**
     * O nome e a assinatura do comando.
     *
     * @var string
     */
    protected $signature = 'fiscal:analytics-update {--tenant_id= : ID do tenant específico para atualizar (opcional)}';

    /**
     * A descrição do comando.
     *
     * @var string
     */
    protected $description = 'Atualiza o cache de dados analíticos para dashboards';

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

            $this->updateOrganizationForTenantCache($tenant);
            $this->info("Cache analítico atualizado para o tenant {$tenant->razao_social}.");

            return Command::SUCCESS;
        }

        // Atualizar todos os tenants
        $tenants = Tenant::all();
        $count = $tenants->count();

        $this->info("Iniciando atualização do cache analítico para {$count} tenants...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($tenants as $tenant) {
            $this->updateOrganizationForTenantCache($tenant);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Cache analítico atualizado com sucesso para todos os tenants.');

        return Command::SUCCESS;
    }

    /**
     * Atualiza o cache para as organizações do tenant específico
     */
    private function updateOrganizationForTenantCache(Tenant $tenant): void
    {
        tenancy()->initialize($tenant->id);

        Organization::all()
            ->each(function ($organization) use ($tenant) {
                $this->info("Atualizando cache para a organização {$organization->cnpj} do tenant {$tenant->id}...");
                UpdateAnalyticsCache::dispatch(
                    $organization->id,
                    $organization->cnpj
                );
            });

        tenancy()->end();
    }
}
