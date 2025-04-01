<?php

namespace App\Console\Commands;

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
    protected $signature = 'analytics:update {--organization= : ID da organização específica para atualizar (opcional)}';

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
        $organizationId = $this->option('organization');
        
        if ($organizationId) {
            // Atualizar apenas uma organização específica
            $organization = Organization::find($organizationId);
            
            if (!$organization) {
                $this->error("Organização com ID {$organizationId} não encontrada.");
                return Command::FAILURE;
            }
            
            $this->updateOrganizationCache($organization);
            $this->info("Cache analítico atualizado para {$organization->razao_social}.");
            
            return Command::SUCCESS;
        }
        
        // Atualizar todas as organizações ativas
        $organizations = Organization::where('active', true)->get();
        $count = $organizations->count();
        
        $this->info("Iniciando atualização do cache analítico para {$count} organizações...");
        
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        
        foreach ($organizations as $organization) {
            $this->updateOrganizationCache($organization);
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('Cache analítico atualizado com sucesso.');
        
        return Command::SUCCESS;
    }
    
    /**
     * Atualiza o cache para uma organização específica
     */
    private function updateOrganizationCache(Organization $organization): void
    {
        UpdateAnalyticsCache::dispatch(
            $organization->id,
            $organization->cnpj
        );
    }
} 